<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f9f9f9;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            padding: 20px;
        }
        .header {
            background-color: #ff6f00;
            color: white;
            text-align: center;
            padding: 20px 0;
        }
        .header h2 {
            margin: 0;
            font-size: 24px;
            text-transform: uppercase;
        }
        .info {
            margin: 20px 0;
            font-size: 14px;
        }
        .info p {
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
            padding: 12px;
            font-size: 14px;
            text-align: left;
        }
        td {
            padding: 10px;
            font-size: 14px;
        }
        .totals td {
            border: none;
        }
        .totals tr:last-child td {
            font-weight: bold;
            font-size: 16px;
        }
        .note {
            margin-top: 20px;
            font-size: 12px;
            color: #555;
        }
        .signature-section {
            margin-top: 30px;
        }
        .signature-section p {
            margin: 0;
            font-size: 14px;
        }
        .signature {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
        }
        .signature div {
            width: 200px;
            border-bottom: 1px solid #ddd;
        }
        .signature span {
            font-size: 14px;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Quotation</h2>
        </div>

        <div class="info">
            <p><strong>Name: </strong>{{$data['user_details']->f_name . ' ' . $data['user_details']->l_name }}</p>
            <p><strong>Phone Number: </strong>{{$data['user_details']->phone }}</p>
            <p><strong>Email: </strong>{{$data['user_details']->email }}</p>
            <p><strong>Date:</strong> {{date('d M Y')}}</p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Service Name</th>
                    <th>Estimated Cost</th>
                    <th>Final Cost</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{$data['item_details']->name}}</td>
                    <td>{{_price($data['est_price'])}}</td>
                    <td>{{_price($data['final_price'])}}</td>
                </tr>
            </tbody>
        </table>

        <table class="totals">
            <tr>
                <td>Total Estimated Cost:</td>
                <td>{{_price($data['est_price'])}}</td>
            </tr>
            <tr>
                <td>Net Cost:</td>
                <td>{{_price($data['final_price'])}}</td>
            </tr>
        </table>

        <p class="note">Note: Please note that the cost quoted is just an estimation.</p>

        <div class="signature-section">
        <p>{{$data['store_name']}}</p>
        </div>
    </div>
</body>
</html>
