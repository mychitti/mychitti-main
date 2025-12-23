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
            padding: 20px;
            line-height: 1.6;
        }
        .header {
            background-color: #a51d1d;
            color: white;
            text-align: center;
            padding: 20px 0;
            border-radius: 8px 8px 0 0;
        }
        .header h2 {
            margin: 0;
            font-size: 24px;
        }
        .info {
            margin: 20px 0;
        }
        .info p {
            margin: 5px 0;
        }
        .info p strong {
            font-size: 14px;
            color: #a51d1d;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            font-size: 14px;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .totals tr td {
            border: none;
            font-weight: bold;
        }
        .totals tr td:last-child {
            text-align: right;
        }
        .note {
            font-size: 12px;
            color: #555;
            margin-top: 20px;
        }
        .signature {
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>QUOTATION</h2>
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
        
        <div class="signature">
            <p>{{$data['store_name']}}</p>
        </div>
    </div>
</body>
</html>
