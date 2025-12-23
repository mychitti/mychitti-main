<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Card UI</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f8fafc;
            padding: 2rem;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .dashboard-title {
            font-size: 2rem;
            font-weight: bold;
            color: #1a202c;
            margin-bottom: 2rem;
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 1.5rem;
        }

        .job-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
            padding: 1.5rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .job-card:hover {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .task-id {
            font-family: 'Monaco', 'Menlo', monospace;
            font-size: 0.75rem;
            color: #64748b;
            margin-right: 0.5rem;
        }

        .priority-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            border: 1px solid;
        }

        .priority-high {
            background-color: #fef2f2;
            color: #dc2626;
            border-color: #fecaca;
        }

        .priority-medium {
            background-color: #fffbeb;
            color: #d97706;
            border-color: #fed7aa;
        }

        .priority-low {
            background-color: #f0fdf4;
            color: #16a34a;
            border-color: #bbf7d0;
        }

        .status-container {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .status-icon {
            width: 16px;
            height: 16px;
            border-radius: 50%;
        }

        .status-completed {
            background-color: #16a34a;
            position: relative;
        }

        .status-completed::after {
            content: '✓';
            position: absolute;
            color: white;
            font-size: 10px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .status-progress {
            background-color: #3b82f6;
            position: relative;
        }

        .status-progress::after {
            content: '!';
            position: absolute;
            color: white;
            font-size: 10px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .status-pending {
            background-color: #6b7280;
            border: 2px solid #d1d5db;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status-completed-badge {
            background-color: #dcfce7;
            color: #166534;
        }

        .status-progress-badge {
            background-color: #dbeafe;
            color: #1d4ed8;
        }

        .status-pending-badge {
            background-color: #f3f4f6;
            color: #374151;
        }

        .task-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 0.5rem;
        }

        .task-description {
            color: #64748b;
            font-size: 0.875rem;
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .assignee-section {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .avatar {
            width: 32px;
            height: 32px;
            background-color: #3b82f6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .assignee-info h4 {
            font-size: 0.875rem;
            font-weight: 500;
            color: #1a202c;
        }

        .assignee-info p {
            font-size: 0.75rem;
            color: #64748b;
        }

        .progress-section {
            margin-bottom: 1rem;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.25rem;
        }

        .progress-label {
            font-size: 0.75rem;
            font-weight: 500;
            color: #374151;
        }

        .progress-hours {
            font-size: 0.75rem;
            color: #64748b;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background-color: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background-color: #3b82f6;
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .due-date {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
            color: #64748b;
        }

        .calendar-icon {
            width: 16px;
            height: 16px;
            color: #9ca3af;
        }

        .tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.25rem;
            margin-bottom: 1rem;
        }

        .tag {
            padding: 0.25rem 0.5rem;
            background-color: #f3f4f6;
            color: #374151;
            font-size: 0.75rem;
            border-radius: 6px;
        }

        .expand-btn {
            width: 100%;
            background: none;
            border: none;
            color: #3b82f6;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            padding: 0.5rem 0;
            transition: color 0.2s ease;
        }

        .expand-btn:hover {
            color: #1d4ed8;
        }

        .expanded-details {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
            display: none;
        }

        .expanded-details.show {
            display: block;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .detail-label {
            font-size: 0.75rem;
            color: #64748b;
        }

        .detail-value {
            font-size: 0.75rem;
            color: #374151;
        }

        @media (max-width: 768px) {
            .cards-grid {
                grid-template-columns: 1fr;
            }
            
            body {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="dashboard-title">Job Cards Dashboard</h1>
        
        <div class="cards-grid">
            <!-- Job Card 1 -->
            <div class="job-card">
                <div class="card-header">
                    <div>
                        <span class="task-id">TSK-001</span>
                        <span class="priority-badge priority-high">High</span>
                    </div>
                    <div class="status-container">
                        <div class="status-icon status-progress"></div>
                        <span class="status-badge status-progress-badge">In Progress</span>
                    </div>
                </div>
                
                <h3 class="task-title">Update User Dashboard</h3>
                <p class="task-description">Redesign the user dashboard interface with new metrics and improved navigation layout</p>
                
                <div class="assignee-section">
                    <div class="avatar">SJ</div>
                    <div class="assignee-info">
                        <h4>Sarah Johnson</h4>
                        <p>Frontend Development</p>
                    </div>
                </div>
                
                <div class="progress-section">
                    <div class="progress-header">
                        <span class="progress-label">Progress</span>
                        <span class="progress-hours">3h / 8h</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 37.5%"></div>
                    </div>
                </div>
                
                <div class="due-date">
                    <svg class="calendar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <span>Due: July 30, 2025</span>
                </div>
                
                <div class="tags">
                    <span class="tag">UI/UX</span>
                    <span class="tag">React</span>
                    <span class="tag">Dashboard</span>
                </div>
                
                <button class="expand-btn" onclick="toggleDetails(this)">Show Details</button>
                
                <div class="expanded-details">
                    <div class="detail-row">
                        <span class="detail-label">Created:</span>
                        <span class="detail-value">July 20, 2025</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Last Updated:</span>
                        <span class="detail-value">July 25, 2025</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Reporter:</span>
                        <span class="detail-value">Mike Chen</span>
                    </div>
                </div>
            </div>

            <!-- Job Card 2 -->
            <div class="job-card">
                <div class="card-header">
                    <div>
                        <span class="task-id">TSK-002</span>
                        <span class="priority-badge priority-medium">Medium</span>
                    </div>
                    <div class="status-container">
                        <div class="status-icon status-pending"></div>
                        <span class="status-badge status-pending-badge">Pending</span>
                    </div>
                </div>
                
                <h3 class="task-title">Database Optimization</h3>
                <p class="task-description">Optimize database queries and implement caching to improve application performance</p>
                
                <div class="assignee-section">
                    <div class="avatar" style="background-color: #10b981;">DK</div>
                    <div class="assignee-info">
                        <h4>David Kim</h4>
                        <p>Backend Development</p>
                    </div>
                </div>
                
                <div class="progress-section">
                    <div class="progress-header">
                        <span class="progress-label">Progress</span>
                        <span class="progress-hours">0h / 12h</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 0%"></div>
                    </div>
                </div>
                
                <div class="due-date">
                    <svg class="calendar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <span>Due: August 5, 2025</span>
                </div>
                
                <div class="tags">
                    <span class="tag">Database</span>
                    <span class="tag">Performance</span>
                    <span class="tag">SQL</span>
                </div>
                
                <button class="expand-btn" onclick="toggleDetails(this)">Show Details</button>
                
                <div class="expanded-details">
                    <div class="detail-row">
                        <span class="detail-label">Created:</span>
                        <span class="detail-value">July 22, 2025</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Last Updated:</span>
                        <span class="detail-value">July 23, 2025</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Reporter:</span>
                        <span class="detail-value">Alex Rodriguez</span>
                    </div>
                </div>
            </div>

            <!-- Job Card 3 -->
            <div class="job-card">
                <div class="card-header">
                    <div>
                        <span class="task-id">TSK-003</span>
                        <span class="priority-badge priority-low">Low</span>
                    </div>
                    <div class="status-container">
                        <div class="status-icon status-completed"></div>
                        <span class="status-badge status-completed-badge">Completed</span>
                    </div>
                </div>
                
                <h3 class="task-title">API Documentation</h3>
                <p class="task-description">Create comprehensive API documentation for the new authentication endpoints</p>
                
                <div class="assignee-section">
                    <div class="avatar" style="background-color: #8b5cf6;">LW</div>
                    <div class="assignee-info">
                        <h4>Lisa Wang</h4>
                        <p>Documentation</p>
                    </div>
                </div>
                
                <div class="progress-section">
                    <div class="progress-header">
                        <span class="progress-label">Progress</span>
                        <span class="progress-hours">6h / 6h</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 100%"></div>
                    </div>
                </div>
                
                <div class="due-date">
                    <svg class="calendar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <span>Due: July 25, 2025</span>
                </div>
                
                {{-- <div class="tags">
                    <span class="tag">API</span>
                    <span class="tag">Documentation</span>
                    <span class="tag">Auth</span>
                </div> --}}
                
                <button class="expand-btn" onclick="toggleDetails(this)">Show Details</button>
                
                <div class="expanded-details">
                <p>Details here </p>
                    <div class="detail-row">
                        <span class="detail-label">Created:</span>
                        <span class="detail-value">July 15, 2025</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Last Updated:</span>
                        <span class="detail-value">July 25, 2025</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Reporter:</span>
                        <span class="detail-value">Tom Wilson</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleDetails(button) {
            const card = button.closest('.job-card');
            const details = card.querySelector('.expanded-details');
            const isExpanded = details.classList.contains('show');
            
            if (isExpanded) {
                details.classList.remove('show');
                button.textContent = 'Show Details';
            } else {
                details.classList.add('show');
                button.textContent = 'Show Less';
            }
        }
    </script>
</body>
</html>