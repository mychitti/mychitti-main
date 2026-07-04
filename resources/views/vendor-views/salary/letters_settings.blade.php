@extends('layouts.vendor.app')

@section('title', 'HR Letter Templates')

@section('content')
    <div class="content container-fluid">
        <div class="page-header d-flex align-items-center justify-content-between">
            <h1 class="page-header-title">HR Letter Templates</h1>
            <a href="{{ route('vendor.salary.list') }}" class="btn btn-outline-secondary btn-sm">← Back to Salaries</a>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="alert alert-info" style="font-size:13px;">
                    Edit the templates below. These placeholders are replaced with each employee's details when you
                    generate a letter from the salary list:
                    <br>
                    <code>{{ '{{name}}' }}</code> <code>{{ '{{designation}}' }}</code> <code>{{ '{{salary}}' }}</code>
                    <code>{{ '{{joining_date}}' }}</code> <code>{{ '{{date}}' }}</code> <code>{{ '{{store_name}}' }}</code>
                    <code>{{ '{{address}}' }}</code>
                    — and for the termination letter: <code>{{ '{{reason}}' }}</code> <code>{{ '{{last_working_day}}' }}</code>.
                </div>

                <form action="{{ route('vendor.salary.letters.save') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label class="font-weight-bold">Offer Letter</label>
                        <textarea name="offer" class="form-control" rows="10" style="font-family:monospace;">{{ $templates['offer'] }}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Appointment Letter</label>
                        <textarea name="appointment" class="form-control" rows="10" style="font-family:monospace;">{{ $templates['appointment'] }}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Termination Letter</label>
                        <textarea name="termination" class="form-control" rows="10" style="font-family:monospace;">{{ $templates['termination'] }}</textarea>
                    </div>
                    <div class="text-right">
                        <button type="submit" class="btn btn--primary">Save Templates</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
