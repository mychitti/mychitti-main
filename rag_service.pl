import os
import glob
from pathlib import Path
from dotenv import load_dotenv
import voyageai
from qdrant_client import QdrantClient
from qdrant_client.models import Distance, VectorParams, PointStruct

load_dotenv(Path(__file__).parent.parent / ".env")

voyage  = voyageai.Client(api_key=os.getenv("VOYAGE_API_KEY"))
qdrant  = QdrantClient(host="localhost", port=6333)

COLLECTION = "mychitti_knowledge"
VECTOR_SIZE = 512   # voyage-3-lite dimension


# ── helpers ────────────────────────────────────────────────

def ensure_collection():
    """Create Qdrant collection if it doesn't exist."""
    existing = [c.name for c in qdrant.get_collections().collections]
    if COLLECTION not in existing:
        qdrant.create_collection(
            collection_name=COLLECTION,
            vectors_config=VectorParams(size=VECTOR_SIZE, distance=Distance.COSINE),
        )
        print(f"Created collection: {COLLECTION}")


def chunk_text(text: str, chunk_size: int = 500, overlap: int = 50) -> list[str]:
    """Split text into overlapping chunks."""
    words  = text.split()
    chunks = []
    i = 0
    while i < len(words):
        chunk = " ".join(words[i : i + chunk_size])
        chunks.append(chunk)
        i += chunk_size - overlap
    return chunks


# ── indexing ───────────────────────────────────────────────

def index_documents(docs_folder: str = None):
    """Read all .md / .txt files and store in Qdrant."""
    if docs_folder is None:
        docs_folder = str(Path(__file__).parent.parent / "docs")

    ensure_collection()

    files = glob.glob(f"{docs_folder}/**/*.md",  recursive=True) + \
            glob.glob(f"{docs_folder}/**/*.txt", recursive=True)

    if not files:
        print("No documents found in docs/ folder")
        return

    all_chunks  = []
    all_metadata = []

    for filepath in files:
        filename = Path(filepath).name
        text     = open(filepath).read()
        chunks   = chunk_text(text)

        for i, chunk in enumerate(chunks):
            all_chunks.append(chunk)
            all_metadata.append({"source": filename, "chunk_index": i, "text": chunk})

        print(f"  Loaded {len(chunks)} chunks from {filename}")

    # Embed in batches of 50 (Voyage limit)
    points = []
    for i in range(0, len(all_chunks), 50):
        batch      = all_chunks[i : i + 50]
        embeddings = voyage.embed(batch, model="voyage-3-lite").embeddings

        for j, (emb, meta) in enumerate(zip(embeddings, all_metadata[i : i + 50])):
            points.append(PointStruct(id=i + j, vector=emb, payload=meta))

    qdrant.upsert(collection_name=COLLECTION, points=points)
    print(f"\nDone! Indexed {len(points)} chunks into Qdrant.")


# ── retrieval ──────────────────────────────────────────────

def retrieve(query: str, top_k: int = 5) -> list[str]:
    """Find the most relevant chunks for a user question."""
    query_vector = voyage.embed([query], model="voyage-3-lite").embeddings[0]

    results, _ = qdrant.query_points(
        collection_name=COLLECTION,
        query=query_vector,
        limit=top_k,
    )

    return [r.payload["text"] for r in results]


# ── run indexing directly ──────────────────────────────────

if __name__ == "__main__":
    print("Indexing documents...")
    index_documents()