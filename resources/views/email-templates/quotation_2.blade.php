<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f3f4f6;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 650px;
            margin: 40px auto;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        .header {
            background-color: #0fa5e5;
            color: white;
            text-align: center;
            padding: 25px 0;
            border-radius: 10px 10px 0 0;
        }
        .header h2 {
            font-size: 26px;
            margin: 0;
        }
        .details {
            margin: 20px 0;
        }
        .details p {
            font-size: 16px;
            margin: 8px 0;
        }
        .details p strong {
            color: #0fa5e5;
        }
        .table-container {
            margin: 20px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th {
            background-color: #0fa5e5;
            color: white;
            padding: 12px;
            text-align: left;
            font-size: 14px;
        }
        td {
            padding: 10px;
            text-align: left;
            font-size: 14px;
        }
        .totals td {
            font-weight: bold;
        }
        .totals tr td:last-child {
            text-align: right;
        }
        .note {
            margin: 20px 0;
            font-size: 13px;
            color: #555;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .signature {
            margin-top: 30px;
            text-align: right;
            font-size: 16px;
            color: #333;
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

        <div class="table-container">
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
        </div>

        <p class="note">
            Note: Please note that the cost quoted is just an estimation.
        </p>

        <div class="signature">
            <p>{{$data['store_name']}}</p>
        </div>
    </div>
</body>
</html>
