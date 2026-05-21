<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Approved Successfully</title>
  <style>
    body {
      margin: 0;
      padding: 0;
      background: linear-gradient(to right, #e0f7e9, #f0fff5);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }

    .card {
      background: #fff;
      padding: 40px;
      border-radius: 16px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      text-align: center;
      max-width: 400px;
      width: 90%;
      animation: fadeIn 0.6s ease-in-out;
    }

    .card .icon {
      background-color: #28a745;
      color: white;
      width: 80px;
      height: 80px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 36px;
      margin: 0 auto 20px;
    }

    .card h1 {
      margin: 0 0 10px;
      color: #28a745;
      font-size: 24px;
    }

    .card p {
      color: #555;
      margin-bottom: 30px;
    }

    .card a.button {
      display: inline-block;
      padding: 12px 28px;
      background-color: #007bff;
      color: #fff;
      text-decoration: none;
      border-radius: 6px;
      transition: background 0.3s;
    }

    .card a.button:hover {
      background-color: #0056b3;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @media (max-width: 480px) {
      .card {
        padding: 30px 20px;
      }
    }
  </style>
</head>
<body>
  <div class="card">
    <div class="icon">✔</div>
    <h1>Approved Successfully</h1>
    <p>Your action has been completed and approved successfully.</p>
  </div>

</body>
</html>
