<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Hospital Management Module – My Chitti</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --blue: #1565C0;
    --blue-dark: #0D47A1;
    --blue-light: #E3F2FD;
    --blue-mid: #1976D2;
    --green: #2E7D32;
    --green-light: #E8F5E9;
    --green-accent: #43A047;
    --teal: #00695C;
    --teal-light: #E0F2F1;
    --purple: #4527A0;
    --purple-light: #EDE7F6;
    --amber: #E65100;
    --amber-light: #FFF3E0;
    --text: #0D1117;
    --text-muted: #4A5568;
    --text-light: #718096;
    --bg: #FFFFFF;
    --bg-soft: #F8FAFC;
    --bg-card: #FFFFFF;
    --border: #E2E8F0;
    --border-strong: #CBD5E0;
    --shadow: 0 1px 3px rgba(0,0,0,0.08), 0 4px 16px rgba(0,0,0,0.06);
    --shadow-lg: 0 8px 32px rgba(0,0,0,0.10);
    --radius: 12px;
    --radius-lg: 20px;
    --ff: 'Plus Jakarta Sans', sans-serif;
    --ff-serif: 'Instrument Serif', serif;
  }

  body {
    font-family: var(--ff);
    background: var(--bg);
    color: var(--text);
    line-height: 1.7;
    font-size: 16px;
  }

  /* HERO */
  .hero {
    background: linear-gradient(135deg, #0D47A1 0%, #1565C0 40%, #1976D2 70%, #1E88E5 100%);
    padding: 80px 5% 70px;
    position: relative; overflow: hidden;
  }
  .hero::before {
    content: '';
    position: absolute; top: -100px; right: -100px;
    width: 600px; height: 600px;
    background: radial-gradient(circle, rgba(255,255,255,0.06) 0%, transparent 70%);
    border-radius: 50%;
  }
  .hero::after {
    content: '';
    position: absolute; bottom: -80px; left: -80px;
    width: 400px; height: 400px;
    background: radial-gradient(circle, rgba(255,255,255,0.04) 0%, transparent 70%);
    border-radius: 50%;
  }
  .hero-inner { max-width: 900px; margin: 0 auto; position: relative; z-index: 1; text-align: center; }
  .hero-badge {
    display: inline-flex; align-items: center; gap: 8px;
    background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.25);
    border-radius: 100px; padding: 6px 16px; margin-bottom: 28px;
    font-size: 13px; color: rgba(255,255,255,0.9); font-weight: 500;
  }
  .hero-badge span { width: 6px; height: 6px; border-radius: 50%; background: #69F0AE; display: inline-block; }
  .hero h1 {
    font-size: clamp(2.2rem, 5vw, 3.8rem); font-weight: 800;
    color: #fff; line-height: 1.1; margin-bottom: 20px; letter-spacing: -1px;
  }
  .hero h1 em { font-family: var(--ff-serif); font-style: italic; font-weight: 300; }
  .hero p {
    font-size: 1.15rem; color: rgba(255,255,255,0.82); max-width: 640px;
    margin: 0 auto 0; line-height: 1.75;
  }

  /* STATS STRIP */
  .stats-strip {
    background: var(--bg-soft); border-bottom: 1px solid var(--border);
    padding: 28px 5%;
  }
  .stats-inner {
    max-width: 1100px; margin: 0 auto;
    display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 0;
  }
  .stat-item { text-align: center; padding: 16px; border-right: 1px solid var(--border); }
  .stat-item:last-child { border-right: none; }
  .stat-num { font-size: 2rem; font-weight: 800; color: var(--blue); line-height: 1; }
  .stat-label { font-size: 13px; color: var(--text-muted); margin-top: 4px; font-weight: 500; }

  /* SECTIONS */
  section { padding: 80px 5%; }
  .section-inner { max-width: 1100px; margin: 0 auto; }
  .section-label {
    font-size: 12px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase;
    color: var(--blue); margin-bottom: 10px;
  }
  .section-title {
    font-size: clamp(1.6rem, 3vw, 2.4rem); font-weight: 800;
    color: var(--text); line-height: 1.2; margin-bottom: 16px; letter-spacing: -0.5px;
  }
  .section-sub { font-size: 1.05rem; color: var(--text-muted); max-width: 580px; line-height: 1.75; }

  /* OBJECTIVES */
  .objectives-grid {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px; margin-top: 48px;
  }
  .obj-card {
    background: var(--bg-card); border: 1px solid var(--border);
    border-radius: var(--radius-lg); padding: 28px 24px;
    transition: transform 0.2s, box-shadow 0.2s;
  }
  .obj-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); }
  .obj-icon {
    width: 48px; height: 48px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px; margin-bottom: 16px;
  }
  .obj-title { font-size: 15px; font-weight: 700; color: var(--text); margin-bottom: 8px; }
  .obj-desc { font-size: 14px; color: var(--text-muted); line-height: 1.65; }

  /* MODULES */
  .modules-section { background: var(--bg-soft); }
  .modules-intro { margin-bottom: 48px; }
  .modules-grid {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 20px;
  }
  .module-card {
    background: var(--bg-card); border: 1px solid var(--border);
    border-radius: var(--radius-lg); overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
  }
  .module-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-lg); }
  .module-header {
    padding: 20px 24px 16px;
    display: flex; align-items: flex-start; gap: 14px;
    border-bottom: 1px solid var(--border);
  }
  .module-num {
    width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; font-weight: 800; color: #fff; background: var(--blue);
  }
  .module-title-wrap { flex: 1; }
  .module-name { font-size: 16px; font-weight: 700; color: var(--text); line-height: 1.3; }
  .module-tag { font-size: 12px; color: var(--text-light); font-weight: 500; margin-top: 2px; }
  .module-body { padding: 16px 24px 20px; }
  .module-feature {
    display: flex; align-items: flex-start; gap: 10px;
    padding: 7px 0; border-bottom: 1px solid var(--border);
    font-size: 13.5px; color: var(--text-muted); line-height: 1.5;
  }
  .module-feature:last-child { border-bottom: none; }
  .check {
    width: 16px; height: 16px; border-radius: 50%; background: var(--green-light);
    flex-shrink: 0; margin-top: 2px; display: flex; align-items: center; justify-content: center;
  }
  .check::after { content: ''; width: 5px; height: 3px; border-left: 1.5px solid var(--green); border-bottom: 1.5px solid var(--green); transform: rotate(-45deg) translate(1px, -1px); display: block; }

  /* PATIENT FLOW */
  .uf-grid {
    display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 48px; align-items: center;
  }
  .uf-list { display: flex; flex-direction: column; gap: 16px; }
  .uf-item {
    background: var(--bg-card); border: 1px solid var(--border);
    border-radius: var(--radius); padding: 18px 20px;
    display: flex; align-items: flex-start; gap: 14px;
  }
  .uf-icon-wrap {
    width: 40px; height: 40px; border-radius: 10px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center; font-size: 18px;
  }
  .uf-item-title { font-size: 15px; font-weight: 700; color: var(--text); margin-bottom: 4px; }
  .uf-item-desc { font-size: 13.5px; color: var(--text-muted); line-height: 1.6; }
  .uf-visual {
    background: linear-gradient(135deg, var(--blue) 0%, var(--blue-mid) 100%);
    border-radius: var(--radius-lg); padding: 40px 32px; color: #fff;
  }
  .uf-visual-title { font-size: 22px; font-weight: 800; margin-bottom: 24px; }
  .uf-step {
    display: flex; align-items: center; gap: 14px; padding: 14px 0;
    border-bottom: 1px solid rgba(255,255,255,0.15);
  }
  .uf-step:last-child { border-bottom: none; }
  .uf-step-num {
    width: 32px; height: 32px; border-radius: 50%; background: rgba(255,255,255,0.2);
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; font-weight: 700; flex-shrink: 0;
  }
  .uf-step-text { font-size: 14px; color: rgba(255,255,255,0.88); font-weight: 500; }

  /* BENEFITS */
  .benefits-section { background: var(--bg-soft); }
  .benefits-grid {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-top: 48px;
  }
  .benefit-col {
    background: var(--bg-card); border: 1px solid var(--border);
    border-radius: var(--radius-lg); overflow: hidden;
  }
  .benefit-col-header {
    padding: 22px 24px; color: #fff; font-weight: 700; font-size: 17px;
    display: flex; align-items: center; gap: 10px;
  }
  .benefit-col-header.blue { background: var(--blue); }
  .benefit-col-header.green { background: var(--green); }
  .benefit-col-header.purple { background: var(--purple); }
  .benefit-item {
    padding: 14px 20px; border-bottom: 1px solid var(--border);
    font-size: 14px; color: var(--text-muted); display: flex; align-items: flex-start; gap: 10px;
  }
  .benefit-item:last-child { border-bottom: none; }
  .benefit-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; margin-top: 7px; }

  /* SECURITY */
  .security-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 48px; margin-top: 48px; align-items: center; }
  .security-list { display: flex; flex-direction: column; gap: 14px; }
  .sec-item {
    display: flex; align-items: flex-start; gap: 14px;
    padding: 16px; background: var(--bg-card); border: 1px solid var(--border);
    border-radius: var(--radius);
  }
  .sec-badge {
    width: 36px; height: 36px; border-radius: 9px; flex-shrink: 0;
    background: var(--blue-light); display: flex; align-items: center;
    justify-content: center; font-size: 16px;
  }
  .sec-item-text { font-size: 14px; color: var(--text-muted); line-height: 1.6; }
  .sec-item-text strong { color: var(--text); font-weight: 600; display: block; margin-bottom: 2px; }
  .sec-highlight {
    background: linear-gradient(135deg, #0D47A1 0%, #1565C0 100%);
    border-radius: var(--radius-lg); padding: 40px; color: #fff;
  }
  .sec-h-title { font-size: 22px; font-weight: 800; margin-bottom: 8px; }
  .sec-h-sub { font-size: 15px; color: rgba(255,255,255,0.75); margin-bottom: 28px; line-height: 1.65; }
  .sec-pill-wrap { display: flex; flex-wrap: wrap; gap: 10px; }
  .sec-pill {
    background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.25);
    border-radius: 100px; padding: 6px 16px; font-size: 13px; color: rgba(255,255,255,0.9);
    font-weight: 500;
  }

  /* ROADMAP */
  .roadmap-list { display: flex; flex-direction: column; gap: 0; margin-top: 48px; }
  .roadmap-item {
    display: grid; grid-template-columns: 120px 1fr; gap: 0;
    border-bottom: 1px solid var(--border);
  }
  .roadmap-item:last-child { border-bottom: none; }
  .roadmap-phase {
    padding: 24px 20px; border-right: 1px solid var(--border);
    display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px;
  }
  .phase-badge {
    background: var(--blue); color: #fff; border-radius: 100px;
    padding: 4px 14px; font-size: 12px; font-weight: 700;
  }
  .roadmap-body { padding: 24px 28px; }
  .roadmap-feature { font-size: 16px; font-weight: 700; color: var(--text); margin-bottom: 6px; }
  .roadmap-desc { font-size: 14px; color: var(--text-muted); line-height: 1.65; }
  .roadmap-item:nth-child(even) { background: var(--bg-soft); }

  /* FOOTER */
  footer {
    background: #0D1117; color: rgba(255,255,255,0.6);
    padding: 32px 5%; text-align: center;
  }
  footer .footer-logo { font-size: 18px; font-weight: 800; color: #fff; margin-bottom: 6px; }
  footer .footer-logo span { color: #43A047; }
  footer p { font-size: 13.5px; line-height: 1.8; }

  /* RESPONSIVE */
  @media (max-width: 768px) {
    .uf-grid { grid-template-columns: 1fr; }
    .benefits-grid { grid-template-columns: 1fr; }
    .security-grid { grid-template-columns: 1fr; }
    .roadmap-item { grid-template-columns: 1fr; }
    .roadmap-phase { border-right: none; border-bottom: 1px solid var(--border); flex-direction: row; justify-content: flex-start; padding: 14px 20px; }
    .stat-item { border-right: none; border-bottom: 1px solid var(--border); }
  }
</style>
</head>
<body>

<!-- HERO -->
<section class="hero">
  <div class="hero-inner">
    <div class="hero-badge">
      <span></span>
      My Chitti — Hospital Management Module
    </div>
    <h1>Hospital Management,<br><em>Built Into Your Panel</em></h1>
    <p>Everything your hospital needs — OPD, IPD, doctors, nurses, wards, prescriptions, billing, and consent — managed from a single unified dashboard inside the My Chitti store panel.</p>
  </div>
</section>

<!-- STATS -->
<div class="stats-strip">
  <div class="stats-inner">
    <div class="stat-item">
      <div class="stat-num">10+</div>
      <div class="stat-label">Sub-Modules</div>
    </div>
    <div class="stat-item">
      <div class="stat-num">5+</div>
      <div class="stat-label">Staff Roles</div>
    </div>
    <div class="stat-item">
      <div class="stat-num">OPD & IPD</div>
      <div class="stat-label">Full Coverage</div>
    </div>
    <div class="stat-item">
      <div class="stat-num">Real-time</div>
      <div class="stat-label">Bed & Queue Tracking</div>
    </div>
    <div class="stat-item">
      <div class="stat-num">GST</div>
      <div class="stat-label">Compliant Billing</div>
    </div>
  </div>
</div>

<!-- WHAT IS THIS MODULE -->
<section id="about">
  <div class="section-inner">
    <div class="section-label">About This Module</div>
    <h2 class="section-title">What the Hospital Management Module Does</h2>
    <p class="section-sub">When your store's business type is set to <strong>Hospital</strong>, this module activates a dedicated set of features — giving you a fully equipped HMIS right inside My Chitti.</p>
    <div class="objectives-grid">
      <!-- <div class="obj-card">
        <div class="obj-icon" style="background:#E3F2FD;">🏥</div>
        <div class="obj-title">Dedicated Sidebar</div>
        <div class="obj-desc">The panel sidebar reorganizes automatically for hospital stores — showing Hospital Dashboard, Outpatient, Inpatient, Staff, Documents, and Billing as top-level menus.</div>
      </div> -->
      <div class="obj-card">
        <div class="obj-icon" style="background:#E8F5E9;">👤</div>
        <div class="obj-title">Patient-Centered Records</div>
        <div class="obj-desc">Every patient gets a unique ID. All visits, prescriptions, and documents are linked to their profile for instant retrieval across OPD and IPD.</div>
      </div>
      <div class="obj-card">
        <div class="obj-icon" style="background:#EDE7F6;">📋</div>
        <div class="obj-title">Digital Prescriptions</div>
        <div class="obj-desc">Doctors generate digital prescriptions during consultations. Each Rx is printable, linked to the patient, and routed to the pharmacy dispense queue automatically.</div>
      </div>
      <div class="obj-card">
        <div class="obj-icon" style="background:#FFF3E0;">📊</div>
        <div class="obj-title">Live Bed Dashboard</div>
        <div class="obj-desc">Real-time visibility of ward occupancy — Available, Occupied, and Under Cleaning — across all wards and bed tiers from one dashboard screen.</div>
      </div>
      <div class="obj-card">
        <div class="obj-icon" style="background:#E0F2F1;">⚡</div>
        <div class="obj-title">Integrated Billing</div>
        <div class="obj-desc">Generate GST-compliant bills for OPD and IPD visits directly from the panel. All charges — room, procedure, pharmacy — can be itemized on a single invoice.</div>
      </div>
    </div>
  </div>
</section>

<!-- MODULE FEATURES -->
<section class="modules-section" id="modules">
  <div class="section-inner">
    <div class="modules-intro">
      <div class="section-label">Module Breakdown</div>
      <h2 class="section-title">What's Inside the Hospital Module</h2>
      <p class="section-sub">Each sub-module maps to a menu item in your hospital panel sidebar — all interconnected, all accessible from one place.</p>
    </div>
    <div class="modules-grid">

      <div class="module-card">
        <div class="module-header">
          <div class="module-num" style="background:#1565C0;">01</div>
          <div class="module-title-wrap">
            <div class="module-name">Hospital Dashboard</div>
            <div class="module-tag">Real-time hospital overview</div>
          </div>
        </div>
        <div class="module-body">
          <div class="module-feature"><div class="check"></div><span>Live KPI cards: Today's Patients, OPD Visits, IPD Admissions, Bed Availability</span></div>
          <div class="module-feature"><div class="check"></div><span>Trend charts for patient inflow and revenue</span></div>
          <div class="module-feature"><div class="check"></div><span>Alert panel for pending tasks, discharges, and low inventory</span></div>
          <div class="module-feature"><div class="check"></div><span>Department-wise and date-wise filter for all metrics</span></div>
        </div>
      </div>

      <div class="module-card">
        <div class="module-header">
          <div class="module-num" style="background:#1976D2;">02</div>
          <div class="module-title-wrap">
            <div class="module-name">Patient Management</div>
            <div class="module-tag">Complete patient lifecycle</div>
          </div>
        </div>
        <div class="module-body">
          <div class="module-feature"><div class="check"></div><span>Unique Patient ID auto-generated on first registration</span></div>
          <div class="module-feature"><div class="check"></div><span>Demographics: Name, Age, Gender, Blood Group, Contact</span></div>
          <div class="module-feature"><div class="check"></div><span>Complete medical history across all OPD and IPD visits</span></div>
          <div class="module-feature"><div class="check"></div><span>Search by ID, name, contact, or blood group</span></div>
        </div>
      </div>

      <div class="module-card">
        <div class="module-header">
          <div class="module-num" style="background:#1565C0;">03</div>
          <div class="module-title-wrap">
            <div class="module-name">Appointments</div>
            <div class="module-tag">Booking & queue control</div>
          </div>
        </div>
        <div class="module-body">
          <div class="module-feature"><div class="check"></div><span>Online appointment booking via the hospital's My Chitti webpage</span></div>
          <div class="module-feature"><div class="check"></div><span>Doctor-wise scheduling with configurable time slots</span></div>
          <div class="module-feature"><div class="check"></div><span>Token system for OPD walk-in queue management</span></div>
          <div class="module-feature"><div class="check"></div><span>Appointment list view with status tracking in the panel</span></div>
        </div>
      </div>

      <div class="module-card">
        <div class="module-header">
          <div class="module-num" style="background:#0D47A1;">04</div>
          <div class="module-title-wrap">
            <div class="module-name">Doctors</div>
            <div class="module-tag">Profiles & schedules</div>
          </div>
        </div>
        <div class="module-body">
          <div class="module-feature"><div class="check"></div><span>Doctor profiles: Specialization, Qualifications, Experience, Fees</span></div>
          <div class="module-feature"><div class="check"></div><span>Availability scheduling per day and time slot</span></div>
          <div class="module-feature"><div class="check"></div><span>Consultation history per doctor</span></div>
          <div class="module-feature"><div class="check"></div><span>Leave and absence marking to update availability</span></div>
        </div>
      </div>

      <div class="module-card">
        <div class="module-header">
          <div class="module-num" style="background:#2E7D32;">05</div>
          <div class="module-title-wrap">
            <div class="module-name">Nurses</div>
            <div class="module-tag">Ward allocation & task tracking</div>
          </div>
        </div>
        <div class="module-body">
          <div class="module-feature"><div class="check"></div><span>Nurse profiles with department assignment</span></div>
          <div class="module-feature"><div class="check"></div><span>Ward-wise nurse allocation and shift management</span></div>
          <div class="module-feature"><div class="check"></div><span>Task tracking: vitals, medication, ward rounds</span></div>
          <div class="module-feature"><div class="check"></div><span>Duty roster management and attendance records</span></div>
        </div>
      </div>

      <div class="module-card">
        <div class="module-header">
          <div class="module-num" style="background:#1565C0;">06</div>
          <div class="module-title-wrap">
            <div class="module-name">OPD – Outpatient</div>
            <div class="module-tag">Walk-in & queue management</div>
          </div>
        </div>
        <div class="module-body">
          <div class="module-feature"><div class="check"></div><span>Walk-in patient registration at reception</span></div>
          <div class="module-feature"><div class="check"></div><span>Token generation with real-time OPD queue display</span></div>
          <div class="module-feature"><div class="check"></div><span>Doctor assignment and consultation record entry</span></div>
          <div class="module-feature"><div class="check"></div><span>Quick re-registration for returning patients via Patient ID</span></div>
        </div>
      </div>

      <div class="module-card">
        <div class="module-header">
          <div class="module-num" style="background:#1976D2;">07</div>
          <div class="module-title-wrap">
            <div class="module-name">IPD – Inpatient</div>
            <div class="module-tag">Admission, wards & discharge</div>
          </div>
        </div>
        <div class="module-body">
          <div class="module-feature"><div class="check"></div><span>Patient admission with ward and bed selection</span></div>
          <div class="module-feature"><div class="check"></div><span>Daily nursing notes and doctor visit logs per admitted patient</span></div>
          <div class="module-feature"><div class="check"></div><span>Discharge management with summary report</span></div>
          <div class="module-feature"><div class="check"></div><span>IPD billing: room, procedure, pharmacy, investigations</span></div>
        </div>
      </div>

      <div class="module-card">
        <div class="module-header">
          <div class="module-num" style="background:#00695C;">08</div>
          <div class="module-title-wrap">
            <div class="module-name">Wards & Beds</div>
            <div class="module-tag">Real-time occupancy tracking</div>
          </div>
        </div>
        <div class="module-body">
          <div class="module-feature"><div class="check"></div><span>Ward creation: General, ICU, Private, Semi-Private</span></div>
          <div class="module-feature"><div class="check"></div><span>Bed status: Available / Occupied / Under Cleaning</span></div>
          <div class="module-feature"><div class="check"></div><span>Real-time bed occupancy view on the dashboard</span></div>
          <div class="module-feature"><div class="check"></div><span>Occupancy reports for planning and review</span></div>
        </div>
      </div>

      <div class="module-card">
        <div class="module-header">
          <div class="module-num" style="background:#4527A0;">09</div>
          <div class="module-title-wrap">
            <div class="module-name">Prescriptions</div>
            <div class="module-tag">Digital Rx with pharmacy routing</div>
          </div>
        </div>
        <div class="module-body">
          <div class="module-feature"><div class="check"></div><span>Digital prescription generation during doctor consultation</span></div>
          <div class="module-feature"><div class="check"></div><span>Medicine details: Dosage, Frequency, Duration, Instructions</span></div>
          <div class="module-feature"><div class="check"></div><span>Follow-up date scheduling from within the prescription</span></div>
          <div class="module-feature"><div class="check"></div><span>Printable Rx with hospital letterhead and doctor signature</span></div>
        </div>
      </div>

      <div class="module-card">
        <div class="module-header">
          <div class="module-num" style="background:#2E7D32;">10</div>
          <div class="module-title-wrap">
            <div class="module-name">Dispense Queue</div>
            <div class="module-tag">Prescription-linked dispensing</div>
          </div>
        </div>
        <div class="module-body">
          <div class="module-feature"><div class="check"></div><span>Prescription-based medicine dispensing workflow</span></div>
          <div class="module-feature"><div class="check"></div><span>Pharmacy queue with token system</span></div>
          <div class="module-feature"><div class="check"></div><span>Dispensing history linked to patient and prescription</span></div>
          <div class="module-feature"><div class="check"></div><span>Low-inventory alerts for pharmacy stock</span></div>
        </div>
      </div>

      <div class="module-card">
        <div class="module-header">
          <div class="module-num" style="background:#1565C0;">11</div>
          <div class="module-title-wrap">
            <div class="module-name">Billing</div>
            <div class="module-tag">GST-compliant invoice engine</div>
          </div>
        </div>
        <div class="module-body">
          <div class="module-feature"><div class="check"></div><span>OPD and IPD billing with itemized breakdowns</span></div>
          <div class="module-feature"><div class="check"></div><span>GST-compliant invoicing with tax calculation</span></div>
          <div class="module-feature"><div class="check"></div><span>Multiple payment modes: Cash, UPI, Card</span></div>
          <div class="module-feature"><div class="check"></div><span>Pending dues tracking and complete payment history</span></div>
        </div>
      </div>

      <div class="module-card">
        <div class="module-header">
          <div class="module-num" style="background:#1976D2;">12</div>
          <div class="module-title-wrap">
            <div class="module-name">Consent Forms & Documents</div>
            <div class="module-tag">Secure medical record storage</div>
          </div>
        </div>
        <div class="module-body">
          <div class="module-feature"><div class="check"></div><span>Digital consent form creation with reusable templates</span></div>
          <div class="module-feature"><div class="check"></div><span>Consent forms linked to patient and admission record</span></div>
          <div class="module-feature"><div class="check"></div><span>Upload and store X-rays, lab reports, scans, PDFs</span></div>
          <div class="module-feature"><div class="check"></div><span>Role-based secure access to all patient documents</span></div>
        </div>
      </div>

      <div class="module-card">
        <div class="module-header">
          <div class="module-num" style="background:#6A1B9A;">13</div>
          <div class="module-title-wrap">
            <div class="module-name">Pharmacy Inventory</div>
            <div class="module-tag">Available as a separate add-on</div>
          </div>
        </div>
        <div class="module-body">
          <div class="module-feature"><div class="check"></div><span>Medicine catalogue with batch and expiry tracking</span></div>
          <div class="module-feature"><div class="check"></div><span>Stock-in / stock-out with supplier management</span></div>
          <div class="module-feature"><div class="check"></div><span>Purchase order generation and goods receipt</span></div>
          <div class="module-feature"><div class="check"></div><span>Low-stock alerts and auto-reorder triggers</span></div>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- PATIENT FLOW -->
<section id="patient">
  <div class="section-inner">
    <div class="section-label">Patient Journey</div>
    <h2 class="section-title">How a Patient Visit Flows Through the System</h2>
    <p class="section-sub">From appointment booking to billing — every step is tracked, linked, and accessible from the hospital panel.</p>
    <div class="uf-grid">
      <div class="uf-list">
        <div class="uf-item">
          <div class="uf-icon-wrap" style="background:#E3F2FD;">📅</div>
          <div>
            <div class="uf-item-title">Appointment or Walk-In</div>
            <div class="uf-item-desc">Patient books online via the hospital's My Chitti webpage, or walks in and gets registered at the OPD counter with a token.</div>
          </div>
        </div>
        <div class="uf-item">
          <div class="uf-icon-wrap" style="background:#E8F5E9;">🔍</div>
          <div>
            <div class="uf-item-title">Doctor Consultation & Prescription</div>
            <div class="uf-item-desc">Doctor opens the patient record, records the consultation, and generates a digital prescription — auto-routed to the dispense queue.</div>
          </div>
        </div>
        <div class="uf-item">
          <div class="uf-icon-wrap" style="background:#EDE7F6;">🏨</div>
          <div>
            <div class="uf-item-title">Admission (if needed)</div>
            <div class="uf-item-desc">Patient is admitted to a ward with bed selection. Daily nursing notes and doctor visits are logged against the IPD admission record.</div>
          </div>
        </div>
        <div class="uf-item">
          <div class="uf-icon-wrap" style="background:#FFF3E0;">🧾</div>
          <div>
            <div class="uf-item-title">Billing & Discharge</div>
            <div class="uf-item-desc">Final bill is generated with itemized charges — consultation, room, pharmacy, procedures — and the patient is discharged with a summary report.</div>
          </div>
        </div>
      </div>
      <div class="uf-visual">
        <div class="uf-visual-title">OPD Visit — Step by Step</div>
        <div class="uf-step">
          <div class="uf-step-num">1</div>
          <div class="uf-step-text">Patient books appointment or walks in to reception</div>
        </div>
        <div class="uf-step">
          <div class="uf-step-num">2</div>
          <div class="uf-step-text">Staff registers patient and issues OPD token</div>
        </div>
        <div class="uf-step">
          <div class="uf-step-num">3</div>
          <div class="uf-step-text">Doctor opens patient record in panel and consults</div>
        </div>
        <div class="uf-step">
          <div class="uf-step-num">4</div>
          <div class="uf-step-text">Digital prescription created and sent to pharmacy queue</div>
        </div>
        <div class="uf-step">
          <div class="uf-step-num">5</div>
          <div class="uf-step-text">Pharmacist dispenses medicines from the queue</div>
        </div>
        <div class="uf-step">
          <div class="uf-step-num">6</div>
          <div class="uf-step-text">Billing generated and payment recorded in panel</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- BENEFITS -->
<section class="benefits-section" id="benefits">
  <div class="section-inner">
    <div class="section-label">Who Benefits</div>
    <h2 class="section-title">Built for Every Role in the Hospital</h2>
    <p class="section-sub">The hospital module supports distinct access and workflows for every staff role — from admin to doctor to nurse.</p>
    <div class="benefits-grid">
      <div class="benefit-col">
        <div class="benefit-col-header blue">🏥 Hospital Admin</div>
        <div class="benefit-item"><div class="benefit-dot" style="background:#1565C0;"></div><span>Full visibility into daily operations via dashboard</span></div>
        <div class="benefit-item"><div class="benefit-dot" style="background:#1565C0;"></div><span>Staff management: doctors, nurses, roles, permissions</span></div>
        <div class="benefit-item"><div class="benefit-dot" style="background:#1565C0;"></div><span>Billing, reports, and audit access</span></div>
        <div class="benefit-item"><div class="benefit-dot" style="background:#1565C0;"></div><span>Ward and bed configuration from settings</span></div>
        <div class="benefit-item"><div class="benefit-dot" style="background:#1565C0;"></div><span>Consent template creation and management</span></div>
      </div>
      <div class="benefit-col">
        <div class="benefit-col-header green">👨‍⚕️ Doctors</div>
        <div class="benefit-item"><div class="benefit-dot" style="background:#2E7D32;"></div><span>Digital prescription — no handwriting needed</span></div>
        <div class="benefit-item"><div class="benefit-dot" style="background:#2E7D32;"></div><span>Instant access to patient history and past Rx</span></div>
        <div class="benefit-item"><div class="benefit-dot" style="background:#2E7D32;"></div><span>View today's appointments and OPD queue</span></div>
        <div class="benefit-item"><div class="benefit-dot" style="background:#2E7D32;"></div><span>IPD patient round notes and discharge notes</span></div>
        <div class="benefit-item"><div class="benefit-dot" style="background:#2E7D32;"></div><span>Schedule management and leave marking</span></div>
      </div>
      <div class="benefit-col">
        <div class="benefit-col-header purple">🧑‍⚕️ Nurses & Reception</div>
        <div class="benefit-item"><div class="benefit-dot" style="background:#4527A0;"></div><span>OPD token issuance and patient check-in</span></div>
        <div class="benefit-item"><div class="benefit-dot" style="background:#4527A0;"></div><span>Ward assignment and bed allocation</span></div>
        <div class="benefit-item"><div class="benefit-dot" style="background:#4527A0;"></div><span>Nursing notes: vitals, medication, observations</span></div>
        <div class="benefit-item"><div class="benefit-dot" style="background:#4527A0;"></div><span>Consent form issuance and document upload</span></div>
        <div class="benefit-item"><div class="benefit-dot" style="background:#4527A0;"></div><span>Shift and duty roster visibility</span></div>
      </div>
    </div>
  </div>
</section>

<!-- SECURITY -->
<section id="security">
  <div class="section-inner">
    <div class="section-label">Access & Security</div>
    <h2 class="section-title">Role-Based Access, Built In</h2>
    <p class="section-sub">Every staff member in the hospital module has access only to what their role requires — ensuring data privacy and operational integrity.</p>
    <div class="security-grid">
      <div class="security-list">
        <div class="sec-item">
          <div class="sec-badge">🔐</div>
          <div class="sec-item-text"><strong>Role-Based Permissions</strong>Admin, Doctor, Nurse, Receptionist, and Pharmacist each have distinct module access defined from the custom roles settings.</div>
        </div>
        <div class="sec-item">
          <div class="sec-badge">🔒</div>
          <div class="sec-item-text"><strong>Encrypted Patient Data</strong>All patient records are stored encrypted and transmitted over HTTPS — never exposed in plain text.</div>
        </div>
        <div class="sec-item">
          <div class="sec-badge">📋</div>
          <div class="sec-item-text"><strong>Audit Trails</strong>Every data update — prescription, admission, billing, document upload — is logged with user identity and timestamp.</div>
        </div>
        <div class="sec-item">
          <div class="sec-badge">☁️</div>
          <div class="sec-item-text"><strong>Automated Backups</strong>Patient records are backed up regularly. Data is never lost and can be restored at any point if needed.</div>
        </div>
      </div>
      <div class="sec-highlight">
        <div class="sec-h-title">What's Protected</div>
        <div class="sec-h-sub">The hospital module enforces strict data boundaries — staff can only access records relevant to their role and assigned patients.</div>
        <div class="sec-pill-wrap">
          <div class="sec-pill">AES-256 Encryption</div>
          <div class="sec-pill">HTTPS Secured</div>
          <div class="sec-pill">Session Timeout</div>
          <div class="sec-pill">Role Permissions</div>
          <div class="sec-pill">Data Isolation</div>
          <div class="sec-pill">Audit Logging</div>
          <div class="sec-pill">2FA Support</div>
          <div class="sec-pill">IP Restrictions</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ROADMAP -->
<section class="benefits-section" id="roadmap">
  <div class="section-inner">
    <div class="section-label">Coming Soon</div>
    <h2 class="section-title">What's Next for the Hospital Module</h2>
    <p class="section-sub">Planned enhancements to the hospital module based on real usage feedback from hospital partners.</p>
    <div class="roadmap-list" style="background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius-lg); overflow:hidden; margin-top:48px;">
      <div class="roadmap-item">
        <div class="roadmap-phase"><div class="phase-badge">v1.1</div></div>
        <div class="roadmap-body">
          <div class="roadmap-feature">Telemedicine / Video Consultation</div>
          <div class="roadmap-desc">Video consultation between doctors and patients directly through the panel, with integrated digital prescription generation at the end of the call.</div>
        </div>
      </div>
      <div class="roadmap-item">
        <div class="roadmap-phase"><div class="phase-badge">v1.2</div></div>
        <div class="roadmap-body">
          <div class="roadmap-feature">AI-Assisted Patient Insights</div>
          <div class="roadmap-desc">Intelligent risk flagging, chronic disease tracking, and predictive health analytics to assist doctors in proactive patient care decisions.</div>
        </div>
      </div>
      <div class="roadmap-item">
        <div class="roadmap-phase"><div class="phase-badge">v1.3</div></div>
        <div class="roadmap-body">
          <div class="roadmap-feature">Lab & Investigation Module</div>
          <div class="roadmap-desc">Integrated lab test ordering, result entry by lab technicians, and automatic report delivery to the patient's profile and doctor's dashboard.</div>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- FOOTER -->
<footer>
  <div class="footer-logo">My Chitti <span>Technologies</span></div>
  <p>Hospital Management Module &nbsp;|&nbsp; My Chitti Store Panel<br>
  Tirupati, Andhra Pradesh, India &nbsp;|&nbsp; My Chitti Technologies Private Limited</p>
</footer>

</body>
</html>
