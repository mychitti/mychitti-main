<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>TIO Icons Viewer</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f9fafb;
      margin: 20px;
    }
    h2 {
      margin-bottom: 10px;
    }
    .icon-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
      gap: 15px;
    }
    .icon-card {
      background: #fff;
      border-radius: 10px;
      padding: 20px 10px;
      text-align: center;
      cursor: pointer;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .icon-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 4px 16px rgba(0,0,0,0.1);
    }
    .icon-card i {
      font-size: 24px;
      margin-bottom: 8px;
      display: block;
    }
    .icon-name {
      font-size: 12px;
      color: #333;
      word-break: break-word;
    }
    .copied {
      color: green;
      font-weight: bold;
    }
  </style>

  <!-- Load your TIO icons stylesheet -->
    <link rel="stylesheet" href="{{ asset('public/assets/admin') }}/vendor/icon-set/style.css">
</head>
<body>

  <h2>📦 TIO Icons Viewer</h2>
  <p>Click any icon to copy its class name.</p>

  <div id="iconGrid" class="icon-grid"></div>

  <script>
    async function loadIcons() {
      const response = await fetch("{{ asset('public/assets/admin') }}/vendor/icon-set/style.css"); // 👈 same file path as above
      const cssText = await response.text();

      // Regex to capture all ".tio-xxxxx:before" classes
      const matches = cssText.match(/\.tio-[a-z0-9\-]+(?=:before)/g);

      const iconClasses = [...new Set(matches)]; // remove duplicates
      const grid = document.getElementById("iconGrid");

      iconClasses.forEach(cls => {
        const className = cls.replace('.', '');
        const div = document.createElement("div");
        div.className = "icon-card";
        div.innerHTML = `<i class="${className}"></i><div class="icon-name">${className}</div>`;
        div.addEventListener("click", () => {
          navigator.clipboard.writeText(className).then(() => {
            div.querySelector(".icon-name").textContent = className + " ✓ Copied!";
            div.querySelector(".icon-name").classList.add("copied");
            setTimeout(() => {
              div.querySelector(".icon-name").textContent = className;
              div.querySelector(".icon-name").classList.remove("copied");
            }, 1500);
          });
        });
        grid.appendChild(div);
      });
    }

    loadIcons();
  </script>
</body>
</html>
