<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ID Card - {{ $emp->f_name . ' ' . $emp->l_name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        /* Print styles */
        @media print {
            body {
                background: white;
                margin: 0;
                padding: 0;
                min-height: auto;
            }

            .print-button {
                display: none;
            }

            .id-card {
                width: 4.575in;
                height: 3.0in;
                box-shadow: none;
                border: 1px solid #ccc;
                margin: 0;
                transform: none;
            }



            .profile-image {
                width: 100px;
                height: 100px;
            }

            .right-section {
                padding: 20px 18px;
            }

            .company-name {
                font-size: 14px;
                margin-bottom: 15px;
            }

            .employee-name {
                font-size: 12px;
                margin-bottom: 6px;
            }

            .job-title {
                font-size: 13px;
                margin-bottom: 15px;
            }

            .id-label {
                font-size: 12px;
                margin-bottom: 3px;
            }

            .id-number {
                font-size: 14px;
            }

            .signature-section {
                padding-top: 10px;
            }

            .signature-label {
                font-size: 12px;
            }
        }
    </style>
</head>

<body>
   @include('vendor-views.form_modals.id_card')

    <button class="print-button" onclick="window.print()">🖨️ Print ID Card</button>

    <script>
        // Print functionality
        function printCard() {
            window.print();
        }
        // Keyboard shortcut for printing (Ctrl+P) 
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                printCard();
            }
        });
    </script>
</body>

</html>
