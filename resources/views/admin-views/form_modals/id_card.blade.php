<style>
    .id-card {
        background: white;
        width: 500px;
        height: 290px;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        display: flex;
        overflow: hidden;
        position: relative;
    }

    .left-section2 {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        width: 45%;
        display: flex;
        justify-content: center;
        align-items: center;
        position: relative;
    }

    .logo-image {
        position: absolute;
        width: 35px;
        height: 35px;
        left: 14px;
        top: 14px;
    }

    .profile-image {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        border: 4px solid white;
        object-fit: cover;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    }

    .right-section2 {
        width: 55%;
        padding: 30px 25px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .company-name {
        font-size: 16px;
        font-weight: bold;
        color: #2c3e50;
        margin-bottom: 20px;
        text-align: center;
    }

    .employee-info {
        flex-grow: 1;
    }

    .employee-name {
        font-size: 16px;
        font-weight: bold;
        color: #2c3e50;
        margin-bottom: 8px;
    }

    .job-title {
        font-size: 12px;
        color: #7f8c8d;
        margin-bottom: 20px;
    }

    .id-section {
        margin-bottom: 20px;
    }

    .id-label {
        font-size: 12px;
        color: #2c3e50;
        font-weight: bold;
        margin-bottom: 5px;
    }

    .id-number {
        font-size: 12px;
        color: #34495e;
        font-weight: 500;
    }

    .signature-section {
        border-top: 1px solid #ecf0f1;
        padding-top: 15px;
    }

    .signature-label {
        font-size: 10px;
        color: #2c3e50;
        font-weight: bold;
    }

    .signature-line {
        margin-top: 10px;
        height: 2px;
        background: #ecf0f1;
        border-radius: 1px;
    }

    .card-accent {
        position: absolute;
        top: 0;
        right: 0;
        width: 50px;
        height: 50px;
        background: linear-gradient(45deg, #3498db, #2980b9);
        clip-path: polygon(100% 0, 0 0, 100% 100%);
    }

    .print-button {
        position: fixed;
        top: 20px;
        right: 20px;
        background: linear-gradient(45deg, #3498db, #2980b9);
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 25px;
        font-size: 16px;
        font-weight: bold;
        cursor: pointer;
        box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        transition: all 0.3s ease;
        z-index: 1000;
    }

    .print-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(52, 152, 219, 0.4);
    }
</style>
<div class="id-card">
    <div class="left-section2">

        <img src="{{ asset('storage/app/public/business') . '/' . \App\Models\BusinessSetting::where('key', 'logo')->first()?->value }}"
            alt="{{ $emp->f_name . ' ' . $emp->l_name }}" class="logo-image">

        <img src="{{ asset('storage/app/public/admin') . '/' . $emp->image }}"
            alt="{{ $emp->f_name . ' ' . $emp->l_name }}" class="profile-image">
    </div>

    <div class="right-section2">
        <div class="company-name">
            {{ \App\Models\BusinessSetting::where('key', 'business_name')->first()?->value}}
        </div>

        <div class="employee-info">
            <div class="employee-name">
                {{ $emp->f_name . ' ' . $emp->l_name }}
            </div>
            <div class="job-title">{{ $emp->role?->name }}
            </div>
            <div style="display:flex; gap: 15px;">

                <div class="id-section">
                    <div class="id-label">Phone Number</div>
                    <div class="id-number">{{ $emp->phone }}
                    </div>
                </div>
                <div class="id-section">
                    <div class="id-label">ID Number</div>
                    <div class="id-number">{{ $em->employee_id ?? $emp->id }}
                    </div>
                </div>
            </div>
            @if ($emp->emergency_contact_details)
                <div class="id-section">
                    <div class="id-label">Emergency Contact
                    </div>
                    <div class="id-number">
                        {{ json_decode($emp->emergency_contact_details)?->phone }}
                    </div>
                </div>
            @endif
        </div>

        <div class="signature-section">
            <div class="signature-label">Signature</div>
            <div class="signature-line"></div>
        </div>
    </div>
</div>
