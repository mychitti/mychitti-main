<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Barcode Scanner</title>

    <script src="https://unpkg.com/html5-qrcode"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin: 30px;
        }

        #reader {
            width: 100%;
            margin: 0 auto;
        }

        #result {
            margin-top: 20px;
            font-size: 20px;
            color: green;
        }

        button {
            margin-top: 10px;
            padding: 8px 15px;
            font-size: 16px;
        }
    </style>
</head>
 
<body>
    <h2 class="d-flex align-items-center justify-content-center mt-3">
        <a href="javascript:history.back()" style="text-decoration: none; color: inherit; margin-right: 15px;" title="Back">&#10094;</a>
        Scan Barcode
    </h2>
    <div id="reader"></div>
    <!-- Button trigger modal -->
    <button type="button" class="modal_btn" style="visibility: hidden;" data-bs-toggle="modal"
        data-bs-target="#productModal">

    </button>

    <!-- Modal -->
    <div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title sku_show" id="exampleModalLabel">SKU : </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex justify-content-center w-100">
                        <div id="result"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="" class="btn btn-dark item_link">View Details</a>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"
        integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous">
    </script>

    <script>
        let scanner;

        function startScanner() {
            scanner = new Html5Qrcode("reader");

            scanner.start({
                    facingMode: "environment"
                }, {
                    fps: 10,
                    qrbox: 800,
                    formatsToSupport: [
                        Html5QrcodeSupportedFormats.QR_CODE,
                        Html5QrcodeSupportedFormats.EAN_13,
                        Html5QrcodeSupportedFormats.UPC_A,
                        Html5QrcodeSupportedFormats.CODE_128
                    ]
                },
                (decodedText, decodedResult) => {
                    getItemDetail(decodedText);
                },
                (errorMessage) => {
                    console.warn(errorMessage);
                }
            );

        }

        $('#productModal').on('hidden.bs.modal', function() {
            startScanner();
        });
        startScanner();

        function stopScanner() {
            if (scanner) {
                scanner.stop().then(() => {}).catch(err => console.error(err));
            }
        }
        $(document).ready(function() {
            {{-- getItemDetail(3444); --}}
        })

        function getItemDetail(sku) {
            stopScanner();
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: "{{ route('vendor.inventory.item.fetch-by-sku') }}",
                data: {
                    sku_id: sku
                },
                success: function(data) {
                    if (data.status) {
                        $(".modal_btn").click();
                        $("#result").html(data.html)
                        $(".sku_show").text('SKU : ' + sku)
                        $(".item_link").attr('href', data.href)
                    } else {
                        alert('Item with sku : "' + sku + '" not found')
                        startScanner();
                    }
                },
            });
        }
    </script>
</body>

</html>


{{-- {!! DNS1D::getBarcodeSVG('SKU455', 'C128', 2, 60, 'black', true) !!} --}}
