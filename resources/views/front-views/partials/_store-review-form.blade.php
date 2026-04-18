{{-- Reusable Store Review Form Partial --}}
@push('css_or_js')
    <style>
        .review-modal .modal-dialog {
            max-width: 600px;
        }

        .review-form {
            padding: 2rem;
        }

        .review-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 16px 16px 0 0;
            text-align: center;
        }

        .review-form-group {
            margin-bottom: 1.5rem;
        }

        .review-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
            display: block;
        }

        .review-input,
        .review-textarea,
        .review-select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s;
            background: white;
        }

        .review-input:focus,
        .review-textarea:focus,
        .review-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .review-textarea {
            min-height: 120px;
            resize: vertical;
        }

        .star-rating {
            display: flex;
            gap: 4px;
            justify-content: center;
            font-size: 28px;
        }

        .star-rating i {
            color: #d1d5db;
            cursor: pointer;
            transition: color 0.2s;
        }

        .star-rating i.active,
        .star-rating i:hover {
            color: #fbbf24;
        }

        .file-upload-area {
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: #f8fafc;
            min-height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }

        .file-upload-area:hover,
        .file-upload-area.dragover {
            border-color: #667eea;
            background: #eff6ff;
        }

        .file-upload-icon {
            font-size: 48px;
            color: #94a3b8;
            margin-bottom: 1rem;
        }

        .file-preview-container {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 1rem;
        }

        .file-preview {
            position: relative;
            width: 80px;
            height: 80px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .file-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .file-remove {
            position: absolute;
            top: -8px;
            right: -8px;
            width: 24px;
            height: 24px;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            cursor: pointer;
            border: 2px solid white;
        }

        .submit-review-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 14px 32px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s;
        }

        .submit-review-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .error-message {
            color: #ef4444;
            font-size: 14px;
            margin-top: 0.25rem;
        }

        .success-message {
            color: #10b981;
            background: #ecfdf5;
            border: 1px solid #bbf7d0;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: none;
        }

        @media (max-width: 576px) {
            .review-form {
                padding: 1.5rem;
            }

            .star-rating {
                font-size: 24px;
            }
        }
    </style>
@endpush

<!-- MODAL -->
<div class="modal fade review-modal" id="storeReviewModal{{ $store['id'] }}" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-xl">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="review-section">
                    <h2 class="h3 fw-bold mb-2">Share Your Experience</h2>
                    <p class="mb-0 opacity-90">{{ $store['name'] }}</p>
                </div>

                <form id="reviewForm{{ $store['id'] }}" class="review-form" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="store_id" value="{{ $store['id'] }}">

                    <div class="review-form-group">
                        <label class="review-label">Service Name <span class="text-danger">*</span></label>
                        <input type="text" name="service_name" class="review-input" required maxlength="255"
                            placeholder="e.g. Laundry Service">
                        <div class="error-message" id="service_name_error{{ $store['id'] }}"></div>
                    </div>

                    <div class="review-form-group">
                        <label class="review-label">Service Date <span class="text-danger">*</span></label>
                        <input type="date" name="service_date" class="review-input" required>
                        <div class="error-message" id="service_date_error{{ $store['id'] }}"></div>
                    </div>

                    <div class="review-form-group">
                        <label class="review-label">Experience <span class="text-danger">*</span></label>
                        <select name="experience" class="review-select" required>
                            <option value="">Select experience</option>
                            <option value="good">Good</option>
                            <option value="bad">Bad</option>
                        </select>
                        <div class="error-message" id="experience_error{{ $store['id'] }}"></div>
                    </div>

                    <div class="review-form-group">
                        <label class="review-label">Your Rating <span class="text-danger">*</span></label>
                        <div class="star-rating" id="starRating{{ $store['id'] }}">
                            <i class="far fa-star" data-rating="1"></i>
                            <i class="far fa-star" data-rating="2"></i>
                            <i class="far fa-star" data-rating="3"></i>
                            <i class="far fa-star" data-rating="4"></i>
                            <i class="far fa-star" data-rating="5"></i>
                        </div>
                        <input type="hidden" name="rating" id="ratingValue{{ $store['id'] }}" value=""
                            required>
                        <div class="error-message" id="rating_error{{ $store['id'] }}"></div>
                    </div>

                    <div class="review-form-group">
                        <label class="review-label">Comment <span class="text-danger">*</span></label>
                        <textarea name="comment" class="review-textarea" required placeholder="Share your detailed experience..."></textarea>
                        <div class="error-message" id="comment_error{{ $store['id'] }}"></div>
                    </div>

                    <div class="review-form-group">
                        <label class="review-label">Attachments (Optional)</label>
                        <div class="file-upload-area" id="fileUpload{{ $store['id'] }}">
                            <i class="fas fa-cloud-upload-alt file-upload-icon"></i>
                            <p class="mb-0" style="color: #64748b;">Click or drag images (JPEG, PNG, JPG, GIF - max
                                30MB each)</p>
                            <input type="file" name="attachments[]" id="fileInput{{ $store['id'] }}" multiple
                                accept="image/*" class="d-none">
                        </div>
                        <div class="file-preview-container" id="filePreview{{ $store['id'] }}"></div>
                        <div class="error-message" id="attachments_error{{ $store['id'] }}"></div>
                    </div>

                    <div class="success-message" id="successMessage{{ $store['id'] }}">
                        <i class="fas fa-check-circle me-2"></i>
                        Thank you! Your review has been submitted successfully.
                    </div>

                    <button type="submit" class="submit-review-btn" id="submitBtn{{ $store['id'] }}">
                        <i class="fas fa-paper-plane me-2"></i> Submit Review
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('script_2')
    <script>
        (function() {
            const storeId = {{ $store['id'] }};
            const formId = 'reviewForm' + storeId;

            // Star rating
            const stars = document.querySelectorAll('#starRating' + storeId + ' i');
            stars.forEach(star => {
                star.addEventListener('click', function() {
                    const rating = this.dataset.rating;
                    document.getElementById('ratingValue' + storeId).value = rating;
                    stars.forEach(s => s.classList.remove('active', 'fas', 'text-warning'));
                    stars.forEach((s, index) => {
                        if (index < rating) s.classList.add('active', 'fas', 'text-warning');
                    });
                });
            });

            // File upload
            const fileInput = document.getElementById('fileInput' + storeId);
            const uploadArea = document.getElementById('fileUpload' + storeId);
            const previewContainer = document.getElementById('filePreview' + storeId);
            let filesArray = [];

            fileInput.addEventListener('change', handleFiles);
            uploadArea.addEventListener('click', () => fileInput.click());

            function handleFiles(e) {
                const files = Array.from(fileInput.files);
                files.forEach(file => {
                    if (file.type.startsWith('image/') && file.size <= 30 * 1024 * 1024) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const preview = document.createElement('div');
                            preview.className = 'file-preview';
                            preview.innerHTML = `
                        <img src="${e.target.result}" alt="${file.name}">
                        <div class="file-remove" onclick="this.parentElement.remove()">×</div>
                    `;
                            previewContainer.appendChild(preview);
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }

            // Form submit
            document.getElementById(formId).addEventListener('submit', async function(e) {
                e.preventDefault();
                const formData = new FormData(this);

                const submitBtn = document.getElementById('submitBtn' + storeId);
                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Submitting...';

                try {
                    const response = await fetch('{{ route('submit-service-review') }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                .getAttribute('content')
                        }
                    });
                    const result = await response.json();

                    if (response.ok) {
                        document.getElementById('successMessage' + storeId).style.display = 'block';
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        alert(result.message || 'Error submitting review');
                    }
                } catch (error) {
                    alert('Network error');
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            });
        })();
    </script>
@endpush
