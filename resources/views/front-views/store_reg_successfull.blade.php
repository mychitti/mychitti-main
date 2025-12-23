<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registration Success</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #f4d9f5, #f7e4c8);
      font-family: 'Inter', sans-serif;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }

    .card {
      background: white;
      border-radius: 20px;
      padding: 30px;
      width: 300px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      text-align: center;
      position: relative;
    }

    .card::before,
    .card::after {
      content: '';
      position: absolute;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.2);
      z-index: -1;
    }

    .card::before {
      width: 80px;
      height: 80px;
      top: -40px;
      left: -40px;
    }

    .card::after {
      width: 100px;
      height: 100px;
      bottom: -50px;
      right: -40px;
    }

    .icon {
      width: 60px;
      height: 60px;
      background: #e0f7e8;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 20px;
    }

    .icon svg {
      width: 30px;
      height: 30px;
      fill: #1bc47d;
    }

    h3 {
      margin: 0;
      font-size: 1.2rem;
      color: #333;
      font-weight: 600;
    }

    p {
      font-size: 0.9rem;
      color: #666;
      margin: 10px 0 20px;
    }

    .btn {
      padding: 10px 20px;
      border: none;
      border-radius: 10px;
      background: linear-gradient(to right, #6b47dc, #bb59e0);
      color: white;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.3s ease;
    }

    .btn:hover {
      background: linear-gradient(to right, #5a3ec9, #a54ccc);
    }
  </style>
</head>
<body>
  <div class="card">
    <div class="icon">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
        <path d="M12 0C5.371 0 0 5.371 0 12s5.371 12 12 12 12-5.371 12-12S18.629 0 12 0zm5.707 9.293l-6.414 6.414a1 1 0 01-1.414 0l-3.293-3.293a1 1 0 111.414-1.414l2.586 2.586 5.707-5.707a1 1 0 011.414 1.414z"/>
      </svg>
    </div>
    <h3> Registration Completed</h3>
    <p>Thank you for registering with Mychitti. Our team will review your application and get back to you soon.</p>
    <a href="{{route('home')}}" class="btn">Continue to Website</a>
  </div>
</body>
</html>
