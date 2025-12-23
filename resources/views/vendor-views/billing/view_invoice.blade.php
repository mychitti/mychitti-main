<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
    body{
        background: #eff6ff;
    }
        .invoice-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .btn-designer {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            font-size: 16px;
            font-weight: 500;
            border: none;
            border-radius: 30px;
            text-decoration: none;
            color: white;
            background: linear-gradient(135deg, #667eea, #764ba2);
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease-in-out;
            cursor: pointer;
        }

        .btn-designer:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
        }

        .icon {
            width: 20px;
            height: 20px;
        }
    </style>

</head>

<body>
    <div class="my-2 container d-flex flex-column align-items-center">
        <div class="invoice-actions">
            <a href="{{ asset('storage/app/public/invoice') . '/' . $invoice->pdf }}" download class="btn-designer download text-white">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4" />
                </svg>
                Download
            </a>

            <button onclick="sharePDF()" class="btn-designer share">
                <i class="fas fa-share-nodes"></i>
                Share
            </button>
            <button onclick="printInvoice()" class="btn-designer print">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M6 9V2h12v7M6 18h12a2 2 0 002-2v-5H4v5a2 2 0 002 2zM6 14h.01" />
                </svg>
                Print
            </button>
        </div>

       <iframe id="invoiceFrame" 
        src="{{ asset('storage/app/public/invoice') . '/' . $invoice->pdf }}#toolbar=0&navpanes=0" 
      width="750" height="1062"></iframe>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"
        integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js"
        integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous">
    </script>
    <script>
        function sharePDF() {
            const pdfUrl = '{{ asset('storage/app/public/invoice') . '/' . $invoice->pdf }}';
            if (navigator.share) {
                navigator.share({
                    title: 'Invoice',
                    text: 'Check out this invoice',
                    url: pdfUrl
                }).catch(err => console.error('Share failed:', err));
            } else {
                alert('Sharing not supported. You can manually copy this URL:\n' + pdfUrl);
            }
        }

        function printInvoice() {
            const iframe = document.getElementById('invoiceFrame');
            iframe.focus();
            iframe.contentWindow.print();
        }
    </script>

</body>

</html>
