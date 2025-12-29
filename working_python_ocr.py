from paddleocr import PaddleOCR
import pprint

image_path = 'uploads/invoice-demo.jpg'

ocr = PaddleOCR(use_textline_orientation=True, lang='en')
result = ocr.predict(image_path)

# Check the full structure
pprint.pprint(result)