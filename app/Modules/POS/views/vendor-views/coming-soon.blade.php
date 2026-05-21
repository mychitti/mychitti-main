@extends('layouts.vendor.app')

@section('title', 'Coming Soon')

@push('css_or_js')
    <style>
    .animated-btn{
        position: absolute;
        right: 0;
    }
        .coming-soon-section {
            background: linear-gradient(135deg, #667eea 0%, #e8e5ff 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center; 
            padding: 2rem;
            width: 100%;
            margin-top: -100px;
        }

        .container {
            text-align: center;
        }

        .coming-soon-title {
            font-size: 4rem;
            font-weight: 700;
            color: white;
            margin-bottom: 2rem;
            letter-spacing: -0.02em;
        }

        .subtitle {
            font-size: 1.125rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 3rem;
            line-height: 1.6;
        }

        .newsletter-form {
            display: flex;
            gap: 0;
            max-width: 400px;
            margin: 0 auto;
            border-radius: 50px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .email-input {
            flex: 1;
            padding: 1rem 1.5rem;
            border: none;
            outline: none;
            font-size: 1rem;
            background: rgba(255, 255, 255, 0.95);
            color: #333;
        }

        .email-input::placeholder {
            color: #999;
        }

        .signup-button {
            padding: 1rem 2rem;
            background: #2d3748;
            color: white;
            border: none;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .signup-button:hover {
            background: #1a202c;
        }

        @media (max-width: 768px) {
            .coming-soon-title {
                font-size: 2.5rem;
            }

            .newsletter-form {
                flex-direction: column;
                border-radius: 12px;
                max-width: 300px;
            }

            .email-input {
                border-radius: 12px 12px 0 0;
            }

            .signup-button {
                border-radius: 0 0 12px 12px;
            }
        }
    </style>
@endpush

@section('content')
    <section class="coming-soon-section">
        <div class="container">
            <h1 class="coming-soon-title">Coming Soon</h1>
            <p class="subtitle">
                In the meantime, Explore our other features.
            </p>
        </div>
    </section>

@endsection

@push('script_2')
@endpush
