<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SEAIT — South East Asian Institute of Technology, Inc.</title>
  <meta name="description" content="South East Asian Institute of Technology, Inc. (SEAIT) — A tuition-free institution committed to the total development of the student. Located in Tupi, South Cotabato, Philippines." />
  <link rel="stylesheet" href="assets/vendor/fonts/fonts.css" />
  <style>
    /* ── Design Tokens (Aura Radiant / Stitch "Simple Orange Dashboard") ── */
    :root {
      --primary:            #ff8c00;
      --primary-dark:       #904d00;
      --primary-light:      #ffb77d;
      --primary-container:  #ffdcc3;
      --on-primary:         #ffffff;
      --surface:            #f8f9fa;
      --surface-container:  #edeeef;
      --surface-high:       #e7e8e9;
      --on-surface:         #191c1d;
      --on-surface-variant: #564334;
      --outline:            #897362;
      --outline-variant:    #ddc1ae;
      --background:         #f8f9fa;
      --white:              #ffffff;
      --tertiary:           #00658f;
      --tertiary-container: #00b5fc;

      --font: 'Work Sans', sans-serif;
      --radius-sm:   4px;
      --radius:      8px;
      --radius-md:   12px;
      --radius-lg:   16px;
      --radius-xl:   24px;
      --radius-full: 9999px;

      --shadow-float: 0 4px 20px rgba(0,0,0,0.05);
      --shadow-card:  0 1px 4px rgba(0,0,0,0.06);
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    html { scroll-behavior: smooth; }

    body {
      font-family: var(--font);
      background: var(--background);
      color: var(--on-surface);
      line-height: 1.6;
      overflow-x: hidden;
    }

    a { text-decoration: none; color: inherit; }
    img { display: block; max-width: 100%; }

    /* ── Utility ── */
    .container {
      width: 100%;
      max-width: 1200px;
      margin-inline: auto;
      padding-inline: 64px;
    }
    @media (max-width: 768px) {
      .container { padding-inline: 16px; }
    }

    .badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: #fff5e6;
      color: var(--primary-dark);
      font-size: 12px;
      font-weight: 600;
      padding: 4px 14px;
      border-radius: var(--radius-full);
      border: 1px solid #ffdcc3;
      letter-spacing: 0.03em;
      text-transform: uppercase;
    }
    .badge::before {
      content: '';
      width: 6px; height: 6px;
      border-radius: 50%;
      background: var(--primary);
      flex-shrink: 0;
    }

    /* ── NAV ── */
    .nav {
      position: fixed;
      top: 0; left: 0; right: 0;
      z-index: 100;
      background: rgba(255,255,255,0.92);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border-bottom: 1px solid var(--surface-high);
      transition: box-shadow 0.2s;
    }
    .nav.scrolled { box-shadow: var(--shadow-float); }
    .nav-inner {
      display: flex;
      align-items: center;
      justify-content: space-between;
      height: 68px;
    }
    .nav-brand {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .nav-logo {
      width: 44px; height: 44px;
      border-radius: var(--radius);
      object-fit: contain;
    }
    .nav-name {
      display: flex;
      flex-direction: column;
      line-height: 1.2;
    }
    .nav-name strong {
      font-size: 15px;
      font-weight: 700;
      color: var(--on-surface);
    }
    .nav-name span {
      font-size: 11px;
      font-weight: 500;
      color: var(--on-surface-variant);
      letter-spacing: 0.02em;
    }
    .nav-links {
      display: flex;
      align-items: center;
      gap: 32px;
      list-style: none;
    }
    .nav-links a {
      font-size: 14px;
      font-weight: 500;
      color: var(--on-surface-variant);
      transition: color 0.15s;
    }
    .nav-links a:hover { color: var(--primary); }
    .nav-cta {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 10px 22px;
      border-radius: var(--radius);
      font-size: 14px;
      font-weight: 600;
      font-family: var(--font);
      cursor: pointer;
      transition: all 0.2s;
      border: none;
      white-space: nowrap;
    }
    .btn-primary {
      background: var(--primary);
      color: var(--on-primary);
    }
    .btn-primary:hover {
      background: var(--primary-dark);
      transform: translateY(-1px);
      box-shadow: 0 6px 20px rgba(255,140,0,0.3);
    }
    .btn-outline {
      background: transparent;
      color: var(--primary);
      border: 1.5px solid var(--primary);
    }
    .btn-outline:hover {
      background: #fff5e6;
      transform: translateY(-1px);
    }
    .btn-lg {
      padding: 14px 32px;
      font-size: 16px;
      border-radius: var(--radius-md);
    }
    .nav-hamburger {
      display: none;
      background: none;
      border: none;
      cursor: pointer;
      padding: 4px;
    }
    @media (max-width: 900px) {
      .nav-links, .nav-cta { display: none; }
      .nav-hamburger { display: flex; flex-direction: column; gap: 5px; }
      .nav-hamburger span {
        display: block; width: 24px; height: 2px;
        background: var(--on-surface); border-radius: 2px;
        transition: all 0.2s;
      }
    }

    /* ── HERO ── */
    .hero {
      padding-top: 140px;
      padding-bottom: 96px;
      position: relative;
      overflow: hidden;
    }
    .hero-bg {
      position: absolute;
      inset: 0;
      background:
        radial-gradient(ellipse 800px 600px at 70% 50%, rgba(255,140,0,0.07) 0%, transparent 70%),
        radial-gradient(ellipse 400px 300px at 10% 80%, rgba(0,101,143,0.05) 0%, transparent 60%);
      pointer-events: none;
    }
    .hero-grid-lines {
      position: absolute;
      inset: 0;
      background-image:
        linear-gradient(rgba(144,77,0,0.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(144,77,0,0.04) 1px, transparent 1px);
      background-size: 48px 48px;
      pointer-events: none;
    }
    .hero-inner {
      position: relative;
      max-width: 840px;
      margin: 0 auto;
      text-align: center;
    }
    .hero-eyebrow { margin-bottom: 20px; }
    .hero h1 {
      font-size: clamp(34px, 5.5vw, 56px);
      font-weight: 800;
      line-height: 1.15;
      letter-spacing: -0.02em;
      color: var(--on-surface);
      margin-bottom: 20px;
    }
    .hero h1 .accent { color: var(--primary); }
    .hero-sub {
      font-size: 18px;
      color: var(--on-surface-variant);
      line-height: 1.65;
      margin-bottom: 36px;
      max-width: 640px;
      margin-inline: auto;
    }
    .hero-actions {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 14px;
      flex-wrap: wrap;
    }
    .hero-stats {
      display: flex;
      justify-content: center;
      gap: 40px;
      margin-top: 52px;
      padding-top: 32px;
      border-top: 1px solid var(--outline-variant);
      flex-wrap: wrap;
    }
    .stat-item {}
    .stat-value {
      font-size: 28px;
      font-weight: 700;
      color: var(--primary);
      line-height: 1;
    }
    .stat-label {
      font-size: 13px;
      color: var(--on-surface-variant);
      margin-top: 4px;
    }
    @media (max-width: 900px) {
      .hero-inner { gap: 40px; }
    }

    /* ── SECTION COMMONS ── */
    section { padding-block: 96px; }
    .section-eyebrow { text-align: center; margin-bottom: 16px; }
    .section-title {
      text-align: center;
      font-size: clamp(24px, 3.5vw, 36px);
      font-weight: 700;
      letter-spacing: -0.01em;
      margin-bottom: 16px;
      color: var(--on-surface);
    }
    .section-sub {
      text-align: center;
      font-size: 17px;
      color: var(--on-surface-variant);
      max-width: 560px;
      margin-inline: auto;
      line-height: 1.65;
    }
    hr.section-divider {
      border: none;
      border-top: 1px solid var(--surface-high);
    }

    /* ── STATS BAND ── */
    .stats-band {
      background: var(--on-surface);
      padding-block: 56px;
    }
    .stats-band-inner {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 32px;
      text-align: center;
    }
    .sb-value {
      font-size: 40px;
      font-weight: 800;
      color: var(--primary);
      line-height: 1;
      margin-bottom: 8px;
    }
    .sb-label {
      font-size: 14px;
      font-weight: 500;
      color: rgba(255,255,255,0.7);
    }
    @media (max-width: 768px) {
      .stats-band-inner { grid-template-columns: repeat(2, 1fr); }
    }

    /* ── VALUES ── */
    .values { background: var(--white); }
    .values-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-top: 56px;
    }
    .value-card {
      background: var(--surface);
      border: 1px solid var(--surface-high);
      border-radius: var(--radius-lg);
      padding: 28px 24px;
      transition: all 0.25s;
    }
    .value-card:hover {
      border-color: var(--primary-light);
      box-shadow: 0 8px 32px rgba(255,140,0,0.1);
      transform: translateY(-3px);
    }
    .value-icon {
      width: 48px; height: 48px;
      border-radius: var(--radius);
      background: #fff5e6;
      display: flex; align-items: center; justify-content: center;
      font-size: 22px;
      margin-bottom: 16px;
    }
    .value-card h3 {
      font-size: 16px;
      font-weight: 700;
      color: var(--on-surface);
      margin-bottom: 8px;
    }
    .value-card p {
      font-size: 14px;
      color: var(--on-surface-variant);
      line-height: 1.6;
    }

    /* ── PROGRAMS ── */
    .programs { background: var(--surface); }
    .programs-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 20px;
      margin-top: 56px;
    }
    .prog-card {
      background: var(--white);
      border: 1px solid var(--surface-high);
      border-radius: var(--radius-lg);
      padding: 28px;
      transition: all 0.25s;
      position: relative;
      overflow: hidden;
    }
    .prog-card::before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0;
      height: 3px;
      background: var(--primary);
      transform: scaleX(0);
      transform-origin: left;
      transition: transform 0.3s;
    }
    .prog-card:hover::before { transform: scaleX(1); }
    .prog-card:hover {
      box-shadow: 0 12px 40px rgba(0,0,0,0.08);
      transform: translateY(-4px);
    }
    .prog-icon {
      font-size: 28px;
      margin-bottom: 14px;
    }
    .prog-card h3 {
      font-size: 16px;
      font-weight: 700;
      color: var(--on-surface);
      margin-bottom: 10px;
    }
    .prog-count {
      font-size: 13px;
      color: var(--primary-dark);
      font-weight: 600;
      margin-bottom: 12px;
    }
    .prog-list {
      list-style: none;
    }
    .prog-list li {
      font-size: 13px;
      color: var(--on-surface-variant);
      padding: 5px 0;
      border-bottom: 1px solid var(--surface);
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .prog-list li:last-child { border-bottom: none; }
    .prog-list li::before {
      content: '›';
      color: var(--primary);
      font-weight: 700;
      flex-shrink: 0;
    }

    /* ── TIMELINE ── */
    .timeline-section { background: var(--white); }
    .timeline {
      position: relative;
      margin-top: 56px;
      padding-left: 32px;
    }
    .timeline::before {
      content: '';
      position: absolute;
      left: 0; top: 0; bottom: 0;
      width: 2px;
      background: linear-gradient(to bottom, var(--primary), var(--primary-light), transparent);
    }
    .timeline-item {
      position: relative;
      padding: 0 0 36px 32px;
      opacity: 0;
      transform: translateX(-20px);
      transition: all 0.5s ease;
    }
    .timeline-item.visible {
      opacity: 1;
      transform: translateX(0);
    }
    .timeline-item::before {
      content: '';
      position: absolute;
      left: -5px; top: 4px;
      width: 10px; height: 10px;
      border-radius: 50%;
      background: var(--primary);
      border: 2px solid var(--white);
      box-shadow: 0 0 0 2px var(--primary);
    }
    .timeline-year {
      font-size: 12px;
      font-weight: 700;
      color: var(--primary);
      letter-spacing: 0.08em;
      text-transform: uppercase;
      margin-bottom: 4px;
    }
    .timeline-text {
      font-size: 15px;
      color: var(--on-surface-variant);
      line-height: 1.55;
    }

    /* ── FACILITIES ── */
    .facilities { background: var(--surface); }
    .facilities-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
      gap: 14px;
      margin-top: 56px;
    }
    .facility-item {
      background: var(--white);
      border: 1px solid var(--surface-high);
      border-radius: var(--radius-lg);
      padding: 20px 16px;
      text-align: center;
      transition: all 0.2s;
      cursor: default;
    }
    .facility-item:hover {
      border-color: var(--primary-light);
      background: #fff5e6;
    }
    .facility-item .icon { font-size: 28px; margin-bottom: 10px; }
    .facility-item span {
      font-size: 13px;
      font-weight: 600;
      color: var(--on-surface-variant);
    }

    /* ── ADMISSION ── */
    .admission { background: var(--white); }
    .admission-tabs {
      display: flex;
      gap: 8px;
      justify-content: center;
      margin-block: 40px 48px;
      flex-wrap: wrap;
    }
    .tab-btn {
      padding: 10px 24px;
      border-radius: var(--radius-full);
      border: 1.5px solid var(--outline-variant);
      background: transparent;
      font-family: var(--font);
      font-size: 14px;
      font-weight: 600;
      color: var(--on-surface-variant);
      cursor: pointer;
      transition: all 0.2s;
    }
    .tab-btn.active, .tab-btn:hover {
      background: var(--primary);
      border-color: var(--primary);
      color: var(--white);
    }
    .tab-panel { display: none; }
    .tab-panel.active { display: block; }
    .admission-steps {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
    }
    .step-card {
      background: var(--surface);
      border-radius: var(--radius-lg);
      padding: 24px;
      position: relative;
    }
    .step-num {
      width: 36px; height: 36px;
      border-radius: 50%;
      background: var(--primary);
      color: var(--white);
      display: flex; align-items: center; justify-content: center;
      font-weight: 700; font-size: 15px;
      margin-bottom: 14px;
    }
    .step-card h4 {
      font-size: 15px;
      font-weight: 700;
      margin-bottom: 6px;
      color: var(--on-surface);
    }
    .step-card p {
      font-size: 13px;
      color: var(--on-surface-variant);
      line-height: 1.55;
    }

    /* ── CTA BANNER ── */
    .cta-banner {
      background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 60%, #ffb300 100%);
      padding-block: 80px;
      text-align: center;
      position: relative;
      overflow: hidden;
    }
    .cta-banner::before {
      content: '';
      position: absolute;
      inset: 0;
      background:
        radial-gradient(ellipse 600px 400px at 80% 50%, rgba(255,255,255,0.08) 0%, transparent 60%);
      pointer-events: none;
    }
    .cta-banner h2 {
      font-size: clamp(28px, 4vw, 44px);
      font-weight: 800;
      color: var(--white);
      letter-spacing: -0.02em;
      margin-bottom: 16px;
    }
    .cta-banner p {
      font-size: 17px;
      color: rgba(255,255,255,0.85);
      margin-bottom: 36px;
      max-width: 520px;
      margin-inline: auto;
    }
    .cta-banner-actions {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 14px;
      flex-wrap: wrap;
    }
    .btn-white {
      background: var(--white);
      color: var(--primary-dark);
    }
    .btn-white:hover {
      background: var(--primary-container);
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    }
    .btn-outline-white {
      background: transparent;
      color: var(--white);
      border: 1.5px solid rgba(255,255,255,0.6);
    }
    .btn-outline-white:hover {
      background: rgba(255,255,255,0.12);
      border-color: var(--white);
    }

    /* ── CONTACT ── */
    .contact { background: var(--surface); }
    .contact-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 48px;
      margin-top: 56px;
      align-items: start;
    }
    @media (max-width: 768px) {
      .contact-grid { grid-template-columns: 1fr; }
    }
    .contact-info-card {
      background: var(--white);
      border-radius: var(--radius-xl);
      padding: 36px;
      border: 1px solid var(--surface-high);
    }
    .contact-info-card h3 {
      font-size: 20px;
      font-weight: 700;
      margin-bottom: 24px;
      color: var(--on-surface);
    }
    .contact-row {
      display: flex;
      align-items: flex-start;
      gap: 14px;
      padding: 14px 0;
      border-bottom: 1px solid var(--surface);
    }
    .contact-row:last-child { border-bottom: none; padding-bottom: 0; }
    .contact-icon {
      width: 38px; height: 38px;
      border-radius: var(--radius);
      background: #fff5e6;
      display: flex; align-items: center; justify-content: center;
      font-size: 18px;
      flex-shrink: 0;
    }
    .contact-row-text strong { display: block; font-size: 13px; font-weight: 700; color: var(--on-surface); }
    .contact-row-text span  { font-size: 13px; color: var(--on-surface-variant); }
    .contact-hours {
      background: var(--on-surface);
      border-radius: var(--radius-xl);
      padding: 36px;
      color: var(--white);
    }
    .contact-hours h3 {
      font-size: 20px;
      font-weight: 700;
      margin-bottom: 8px;
    }
    .contact-hours p {
      font-size: 14px;
      color: rgba(255,255,255,0.65);
      margin-bottom: 28px;
    }
    .hours-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 12px 0;
      border-bottom: 1px solid rgba(255,255,255,0.08);
      font-size: 14px;
    }
    .hours-row:last-child { border-bottom: none; }
    .hours-row .label { color: rgba(255,255,255,0.7); }
    .hours-row .value { font-weight: 600; color: var(--primary-light); }
    .hours-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: rgba(255,140,0,0.15);
      border: 1px solid rgba(255,140,0,0.3);
      border-radius: var(--radius-full);
      padding: 6px 14px;
      font-size: 13px;
      font-weight: 600;
      color: var(--primary-light);
      margin-top: 20px;
    }
    .hours-badge::before {
      content: '';
      width: 7px; height: 7px;
      border-radius: 50%;
      background: #4ade80;
      animation: pulse 2s infinite;
    }
    @keyframes pulse {
      0%, 100% { opacity: 1; }
      50%       { opacity: 0.4; }
    }

    /* ── FOOTER ── */
    .footer {
      background: #0f1011;
      color: rgba(255,255,255,0.7);
      padding-block: 56px 32px;
    }
    .footer-grid {
      display: grid;
      grid-template-columns: 2fr 1fr 1fr 1.5fr;
      gap: 48px;
      margin-bottom: 48px;
    }
    @media (max-width: 900px) {
      .footer-grid { grid-template-columns: 1fr 1fr; gap: 32px; }
    }
    @media (max-width: 560px) {
      .footer-grid { grid-template-columns: 1fr; }
    }
    .footer-brand-logo {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 16px;
    }
    .footer-logo-img {
      width: 40px; height: 40px;
      border-radius: var(--radius);
      object-fit: contain;
    }
    .footer-brand-name {
      font-size: 15px;
      font-weight: 700;
      color: var(--white);
    }
    .footer-brand p {
      font-size: 14px;
      line-height: 1.65;
      margin-bottom: 20px;
    }
    .footer-col h4 {
      font-size: 13px;
      font-weight: 700;
      color: var(--white);
      letter-spacing: 0.06em;
      text-transform: uppercase;
      margin-bottom: 16px;
    }
    .footer-col ul { list-style: none; }
    .footer-col li {
      font-size: 14px;
      padding: 5px 0;
    }
    .footer-col a { color: rgba(255,255,255,0.6); transition: color 0.15s; }
    .footer-col a:hover { color: var(--primary-light); }
    .footer-bottom {
      border-top: 1px solid rgba(255,255,255,0.08);
      padding-top: 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 12px;
      font-size: 13px;
    }
    .footer-bottom-left { color: rgba(255,255,255,0.45); }
    .footer-bottom-left a { color: var(--primary-light); }
    .footer-seal {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: rgba(255,140,0,0.1);
      border: 1px solid rgba(255,140,0,0.25);
      border-radius: var(--radius-full);
      padding: 5px 12px;
      font-size: 12px;
      font-weight: 600;
      color: var(--primary-light);
    }

    /* ── SCROLL ANIMATIONS ── */
    .fade-up {
      opacity: 0;
      transform: translateY(30px);
      transition: opacity 0.6s ease, transform 0.6s ease;
    }
    .fade-up.visible {
      opacity: 1;
      transform: translateY(0);
    }

    /* ── MOBILE NAV DRAWER ── */
    .mobile-nav {
      display: none;
      position: fixed;
      inset: 0;
      z-index: 999;
      background: rgba(15, 23, 42, 0.6);
      backdrop-filter: blur(4px);
      -webkit-backdrop-filter: blur(4px);
    }
    .mobile-nav.open { display: flex; }
    .mobile-nav-panel {
      background: var(--white);
      width: 82%;
      max-width: 320px;
      height: 100%;
      padding: 24px 20px;
      display: flex;
      flex-direction: column;
      gap: 4px;
      box-shadow: 0 0 50px rgba(0,0,0,0.35);
      animation: slideIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
      overflow-y: auto;
    }
    @keyframes slideIn {
      from { transform: translateX(-100%); }
      to   { transform: translateX(0); }
    }
    .mobile-nav-close {
      align-self: flex-end;
      background: var(--surface);
      border: 1px solid var(--surface-high);
      border-radius: var(--radius);
      width: 36px;
      height: 36px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
      cursor: pointer;
      color: var(--on-surface);
      margin-bottom: 16px;
      transition: all 0.15s ease;
    }
    .mobile-nav-close:hover {
      background: var(--primary-bg);
      color: var(--primary);
    }
    .mobile-nav a {
      display: block;
      padding: 12px 10px;
      font-size: 15px;
      font-weight: 600;
      color: var(--on-surface);
      border-bottom: 1px solid var(--surface-high);
      border-radius: var(--radius);
      transition: all 0.15s ease;
    }
    .mobile-nav a:hover {
      background: var(--primary-bg);
      color: var(--primary);
      padding-left: 14px;
    }
    .mobile-nav-actions { margin-top: 24px; display: flex; flex-direction: column; gap: 10px; }

    /* ── Landing Page Skeleton Loader ── */
    @keyframes skeleton-shimmer {
      0% { background-position: -200% 0; }
      100% { background-position: 200% 0; }
    }
    .landing-skeleton {
      background: linear-gradient(90deg, #edeef0 25%, #f7f8f9 37%, #edeef0 63%);
      background-size: 400% 100%;
      animation: skeleton-shimmer 1.4s cubic-bezier(0.4, 0, 0.2, 1) infinite;
      border-radius: var(--radius-sm);
    }
    #pageSkeletonLoader {
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: #ffffff;
      z-index: 99999;
      display: flex;
      flex-direction: column;
      transition: opacity 0.35s ease, visibility 0.35s ease;
      opacity: 1;
      visibility: visible;
      pointer-events: all;
    }
    #pageSkeletonLoader.fade-out {
      opacity: 0;
      visibility: hidden;
      pointer-events: none;
    }
  </style>
</head>
<body>

<!-- ═══ Page Skeleton Loader ═══ -->
<div id="pageSkeletonLoader" aria-hidden="true">
  <!-- Nav Skeleton -->
  <div style="height:68px;border-bottom:1px solid #e7e8e9;padding:0 64px;display:flex;align-items:center;justify-content:space-between;">
    <div style="display:flex;align-items:center;gap:12px;">
      <div class="landing-skeleton" style="width:40px;height:40px;border-radius:8px;"></div>
      <div>
        <div class="landing-skeleton" style="width:120px;height:16px;margin-bottom:4px;"></div>
        <div class="landing-skeleton" style="width:180px;height:10px;"></div>
      </div>
    </div>
    <div style="display:flex;align-items:center;gap:24px;">
      <div class="landing-skeleton d-none d-md-block" style="width:70px;height:14px;"></div>
      <div class="landing-skeleton d-none d-md-block" style="width:80px;height:14px;"></div>
      <div class="landing-skeleton d-none d-md-block" style="width:75px;height:14px;"></div>
      <div class="landing-skeleton" style="width:100px;height:38px;border-radius:9999px;"></div>
    </div>
  </div>

  <!-- Hero Skeleton -->
  <div style="padding:72px 64px;max-width:1200px;margin:0 auto;width:100%;">
    <div class="landing-skeleton" style="width:240px;height:28px;border-radius:9999px;margin-bottom:24px;"></div>
    <div class="landing-skeleton" style="width:80%;height:52px;margin-bottom:16px;border-radius:8px;"></div>
    <div class="landing-skeleton" style="width:60%;height:48px;margin-bottom:24px;border-radius:8px;"></div>
    <div class="landing-skeleton" style="width:70%;height:20px;margin-bottom:10px;"></div>
    <div class="landing-skeleton" style="width:50%;height:20px;margin-bottom:36px;"></div>
    <div style="display:flex;gap:16px;margin-bottom:56px;">
      <div class="landing-skeleton" style="width:160px;height:48px;border-radius:9999px;"></div>
      <div class="landing-skeleton" style="width:140px;height:48px;border-radius:9999px;"></div>
    </div>

    <!-- Cards Row Skeleton -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:20px;">
      <div class="landing-skeleton" style="height:140px;border-radius:12px;"></div>
      <div class="landing-skeleton" style="height:140px;border-radius:12px;"></div>
      <div class="landing-skeleton" style="height:140px;border-radius:12px;"></div>
      <div class="landing-skeleton" style="height:140px;border-radius:12px;"></div>
    </div>
  </div>
</div>

<!-- ═══ NAVBAR ═══ -->
<nav class="nav" id="navbar" role="navigation" aria-label="Main navigation">
  <div class="container">
    <div class="nav-inner">
      <a href="#" class="nav-brand" id="nav-brand-link">
        <img src="photo/logo.jpg" alt="SEAIT Logo" class="nav-logo" />
        <div class="nav-name">
          <strong>SEAIT</strong>
          <span>South East Asian Institute of Technology</span>
        </div>
      </a>
      <ul class="nav-links" id="nav-links">
        <li><a href="#about">About</a></li>
        <li><a href="#programs">Programs</a></li>
        <li><a href="#facilities">Facilities</a></li>
        <li><a href="#admission">Admission</a></li>
        <li><a href="#contact">Contact</a></li>
      </ul>
      <div class="nav-cta">
        <a href="login.php" class="btn btn-outline" id="nav-login-btn">Log In</a>
        <a href="#admission" class="btn btn-primary" id="nav-apply-btn">Apply Now</a>
      </div>
      <button class="nav-hamburger" aria-label="Open menu" id="hamburger-btn">
        <span></span><span></span><span></span>
      </button>
    </div>
  </div>
</nav>

<!-- Mobile Nav -->
<div class="mobile-nav" id="mobile-nav" role="dialog" aria-modal="true" aria-label="Mobile navigation">
  <div class="mobile-nav-panel">
    <button class="mobile-nav-close" id="mobile-nav-close" aria-label="Close menu">✕</button>
    <a href="#about">About</a>
    <a href="#programs">Programs</a>
    <a href="#facilities">Facilities</a>
    <a href="#admission">Admission</a>
    <a href="#contact">Contact</a>
    <div class="mobile-nav-actions">
      <a href="login.php" class="btn btn-outline" style="justify-content:center;">Log In</a>
      <a href="#admission" class="btn btn-primary" style="justify-content:center;">Apply Now</a>
    </div>
  </div>
  <div style="flex:1;" id="mobile-nav-overlay"></div>
</div>

<!-- ═══ HERO ═══ -->
<section class="hero" id="hero" aria-labelledby="hero-heading">
  <div class="hero-bg"></div>
  <div class="hero-grid-lines"></div>
  <div class="container">
    <div class="hero-inner">
      <div class="hero-content">
        <div class="hero-eyebrow">
          <span class="badge">Tuition-Free • Est. 2006 • Tupi, South Cotabato</span>
        </div>
        <h1 id="hero-heading">
          Quality Education,<br><span class="accent">Zero Tuition.</span><br>Infinite Futures.
        </h1>
        <p class="hero-sub">
          South East Asian Institute of Technology is committed to the total development of the student — offering world-class, tuition-free college education in the heart of South Cotabato.
        </p>
        <div class="hero-actions">
          <a href="#admission" class="btn btn-primary btn-lg" id="hero-apply-btn">
            🎓 Start Pre-Registration
          </a>
          <a href="#programs" class="btn btn-outline btn-lg" id="hero-programs-btn">
            Explore Programs
          </a>
        </div>
        <div class="hero-stats">
          <div class="stat-item">
            <div class="stat-value">100%</div>
            <div class="stat-label">Tuition-Free</div>
          </div>
          <div class="stat-item">
            <div class="stat-value">19+</div>
            <div class="stat-label">Years of Excellence</div>
          </div>
          <div class="stat-item">
            <div class="stat-value">24+</div>
            <div class="stat-label">Degree Programs</div>
          </div>
          <div class="stat-item">
            <div class="stat-value">6</div>
            <div class="stat-label">Academic Colleges</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══ STATS BAND ═══ -->
<div class="stats-band" id="about" aria-label="Key statistics">
  <div class="container">
    <div class="stats-band-inner">
      <div class="fade-up">
        <div class="sb-value">2006</div>
        <div class="sb-label">Year Founded</div>
      </div>
      <div class="fade-up">
        <div class="sb-value">100%</div>
        <div class="sb-label">Tuition-Free Guarantee</div>
      </div>
      <div class="fade-up">
        <div class="sb-value">24+</div>
        <div class="sb-label">Academic Programs</div>
      </div>
      <div class="fade-up">
        <div class="sb-value">6+</div>
        <div class="sb-label">Colleges & Departments</div>
      </div>
    </div>
  </div>
</div>

<!-- ═══ ABOUT / VALUES ═══ -->
<section class="values" aria-labelledby="values-heading">
  <div class="container">
    <div class="section-eyebrow fade-up"><span class="badge">Who We Are</span></div>
    <h2 class="section-title fade-up" id="values-heading">Built on Principles That Last</h2>
    <p class="section-sub fade-up">
      Founded in 2006 by Hon. Reynaldo S. Tamayo Jr. and Mrs. Rochelle P. Tamayo — both DOST scholars — SEAIT was born from a belief that quality education is a right, not a privilege.
    </p>
    <div class="values-grid">
      <div class="value-card fade-up">
        <div class="value-icon">🤝</div>
        <h3>Service</h3>
        <p>Dedicated to exceptional service for students, community, and all stakeholders — always putting people first.</p>
      </div>
      <div class="value-card fade-up">
        <div class="value-icon">⭐</div>
        <h3>Excellence</h3>
        <p>Pursuing the highest standards of academic quality, innovation, and student achievement at every level.</p>
      </div>
      <div class="value-card fade-up">
        <div class="value-icon">⚖️</div>
        <h3>Accountability</h3>
        <p>Upholding responsibility, transparency, and ethical practice across all institutional operations.</p>
      </div>
      <div class="value-card fade-up">
        <div class="value-icon">💡</div>
        <h3>Innovation</h3>
        <p>Embracing creativity and forward-thinking approaches to meet the challenges of an evolving world.</p>
      </div>
      <div class="value-card fade-up">
        <div class="value-icon">🤜</div>
        <h3>Teamwork</h3>
        <p>Fostering collaboration and collective effort toward shared success for students, faculty, and staff.</p>
      </div>
    </div>
  </div>
</section>

<!-- ═══ PROGRAMS ═══ -->
<section class="programs" id="programs" aria-labelledby="programs-heading">
  <div class="container">
    <div class="section-eyebrow fade-up"><span class="badge">Academic Programs</span></div>
    <h2 class="section-title fade-up" id="programs-heading">Find Your Path at SEAIT</h2>
    <p class="section-sub fade-up">From Agriculture to Information Technology, SEAIT offers a diverse range of programs — all tuition-free.</p>
    <div class="programs-grid">
      <div class="prog-card fade-up">
        <div class="prog-icon">🌾</div>
        <h3>College of Agriculture & Fisheries</h3>
        <p class="prog-count">5 Programs</p>
        <ul class="prog-list">
          <li>BS Agriculture – Animal Science</li>
          <li>BS Agriculture – Crop Science</li>
          <li>BS Agriculture – Horticulture</li>
          <li>BS Agriculture – Plant Breeding</li>
          <li>BS Fisheries</li>
        </ul>
      </div>
      <div class="prog-card fade-up">
        <div class="prog-icon">💼</div>
        <h3>College of Business & Good Governance</h3>
        <p class="prog-count">6 Programs</p>
        <ul class="prog-list">
          <li>Bachelor of Public Administration</li>
          <li>BS Accounting Information Systems</li>
          <li>BS Hospitality Management</li>
          <li>BS Social Work</li>
          <li>BS Business Administration</li>
          <li>BS Tourism Management</li>
        </ul>
      </div>
      <div class="prog-card fade-up">
        <div class="prog-icon">💻</div>
        <h3>College of Information & Communication Technology</h3>
        <p class="prog-count">2 Programs</p>
        <ul class="prog-list">
          <li>BS Information Technology</li>
          <li>BS IT – Business Analytics</li>
        </ul>
      </div>
      <div class="prog-card fade-up">
        <div class="prog-icon">⚖️</div>
        <h3>College of Criminal Justice Education</h3>
        <p class="prog-count">1 Program</p>
        <ul class="prog-list">
          <li>BS Criminology</li>
        </ul>
      </div>
      <div class="prog-card fade-up">
        <div class="prog-icon">🏗️</div>
        <h3>Department of Civil Engineering</h3>
        <p class="prog-count">1 Program</p>
        <ul class="prog-list">
          <li>BS Civil Engineering – Structural Engineering</li>
        </ul>
      </div>
      <div class="prog-card fade-up">
        <div class="prog-icon">📖</div>
        <h3>College of Teacher Education</h3>
        <p class="prog-count">9 Programs</p>
        <ul class="prog-list">
          <li>BSEd – English, Filipino, Math, Science</li>
          <li>BSEd – Social Studies</li>
          <li>Bachelor of Elementary Education</li>
          <li>Bachelor of Early Childhood Education</li>
          <li>BTLEd – ICT</li>
        </ul>
      </div>
    </div>
  </div>
</section>

<!-- ═══ TIMELINE ═══ -->
<section class="timeline-section" aria-labelledby="timeline-heading">
  <div class="container">
    <div class="section-eyebrow fade-up"><span class="badge">Milestones</span></div>
    <h2 class="section-title fade-up" id="timeline-heading">A Journey of Growth</h2>
    <p class="section-sub fade-up">From a humble vocational school to a full-fledged academic institution — SEAIT's growth story is one of vision and dedication.</p>
    <div class="timeline">
      <div class="timeline-item">
        <div class="timeline-year">2006</div>
        <div class="timeline-text">SEAIT founded; began offering TESDA 2-Year Diploma programs in Computer Programming (NC IV) and Hardware Servicing (NC II).</div>
      </div>
      <div class="timeline-item">
        <div class="timeline-year">2007 – 2009</div>
        <div class="timeline-text">Expanded with Hotel and Restaurant Management diploma, then CHED-approved BS Information Technology, BS Hotel Management, and BS Business Administration.</div>
      </div>
      <div class="timeline-item">
        <div class="timeline-year">2011 – 2013</div>
        <div class="timeline-text">Added BSEd programs (English & Math), BEEd, BS Civil Engineering, and BS Criminology — becoming a multi-discipline institution.</div>
      </div>
      <div class="timeline-item">
        <div class="timeline-year">2016</div>
        <div class="timeline-text">DepEd recognition granted for the K–12 Program, establishing complete basic education from Kindergarten through Senior High School.</div>
      </div>
      <div class="timeline-item">
        <div class="timeline-year">2019 – 2020</div>
        <div class="timeline-text">BS Fisheries, BS Early Childhood Education, BS Social Work, and BS Agricultural Technology added to the growing roster of programs.</div>
      </div>
      <div class="timeline-item">
        <div class="timeline-year">2022 – 2023</div>
        <div class="timeline-text">Further expansion with specialized Agriculture tracks, BSEd Filipino & General Science, and the Bachelor of Technology & Livelihood Education (ICT).</div>
      </div>
    </div>
  </div>
</section>

<!-- ═══ FACILITIES ═══ -->
<section class="facilities" id="facilities" aria-labelledby="facilities-heading">
  <div class="container">
    <div class="section-eyebrow fade-up"><span class="badge">Campus</span></div>
    <h2 class="section-title fade-up" id="facilities-heading">World-Class Facilities</h2>
    <p class="section-sub fade-up">SEAIT provides modern infrastructure and support services to ensure a rich, holistic learning environment for every student.</p>
    <div class="facilities-grid">
      <div class="facility-item fade-up"><div class="icon">🔬</div><span>Science Laboratories</span></div>
      <div class="facility-item fade-up"><div class="icon">💻</div><span>Computer Laboratories</span></div>
      <div class="facility-item fade-up"><div class="icon">📚</div><span>Learning Center / Library</span></div>
      <div class="facility-item fade-up"><div class="icon">💬</div><span>Guidance Office</span></div>
      <div class="facility-item fade-up"><div class="icon">⚽</div><span>Sports Facilities</span></div>
      <div class="facility-item fade-up"><div class="icon">🍽️</div><span>Campus Canteen</span></div>
      <div class="facility-item fade-up"><div class="icon">🏠</div><span>School Dormitory</span></div>
      <div class="facility-item fade-up"><div class="icon">🎬</div><span>Audio-Visual Room</span></div>
      <div class="facility-item fade-up"><div class="icon">👨‍🍳</div><span>Kitchen & THM Room</span></div>
      <div class="facility-item fade-up"><div class="icon">🎤</div><span>Speech Laboratory</span></div>
      <div class="facility-item fade-up"><div class="icon">🌱</div><span>Agricultural Extensions</span></div>
    </div>
  </div>
</section>

<!-- ═══ ADMISSION ═══ -->
<section class="admission" id="admission" aria-labelledby="admission-heading">
  <div class="container">
    <div class="section-eyebrow fade-up"><span class="badge">How to Apply</span></div>
    <h2 class="section-title fade-up" id="admission-heading">Start Your SEAIT Journey</h2>
    <p class="section-sub fade-up">Follow the simple admission process below and take the first step toward your tuition-free education.</p>

    <div class="admission-tabs" role="tablist">
      <button class="tab-btn active" role="tab" aria-selected="true" aria-controls="tab-college" id="tab-college-btn" onclick="switchTab('college')">College</button>
      <button class="tab-btn" role="tab" aria-selected="false" aria-controls="tab-shs" id="tab-shs-btn" onclick="switchTab('shs')">Senior High School</button>
      <button class="tab-btn" role="tab" aria-selected="false" aria-controls="tab-basic" id="tab-basic-btn" onclick="switchTab('basic')">Basic Education</button>
    </div>

    <div id="tab-college" class="tab-panel active" role="tabpanel">
      <div class="admission-steps">
        <div class="step-card fade-up">
          <div class="step-num">1</div>
          <h4>Application Form</h4>
          <p>Submit the application form along with your program preference.</p>
        </div>
        <div class="step-card fade-up">
          <div class="step-num">2</div>
          <h4>Academic Requirements</h4>
          <p>Provide SHS diploma, transcript of records, and other required certificates.</p>
        </div>
        <div class="step-card fade-up">
          <div class="step-num">3</div>
          <h4>SCAT Examination</h4>
          <p>Take the SEAIT College Admission Test (SCAT) to assess readiness for your program.</p>
        </div>
        <div class="step-card fade-up">
          <div class="step-num">4</div>
          <h4>Interview & Assessment</h4>
          <p>Undergo an interview and skills assessment with your department head.</p>
        </div>
        <div class="step-card fade-up">
          <div class="step-num">5</div>
          <h4>Enrollment & Orientation</h4>
          <p>Complete enrollment, receive your schedule, and attend freshman orientation.</p>
        </div>
      </div>
    </div>
    <div id="tab-shs" class="tab-panel" role="tabpanel">
      <div class="admission-steps">
        <div class="step-card fade-up">
          <div class="step-num">1</div>
          <h4>Application Form</h4>
          <p>Submit the application form with your desired strand preference (ABM, GAS, HUMSS, STEM).</p>
        </div>
        <div class="step-card fade-up">
          <div class="step-num">2</div>
          <h4>Academic Records</h4>
          <p>Submit Grade 10 completion certificate and report cards.</p>
        </div>
        <div class="step-card fade-up">
          <div class="step-num">3</div>
          <h4>Aptitude Test</h4>
          <p>Take a career assessment to guide your strand selection.</p>
        </div>
        <div class="step-card fade-up">
          <div class="step-num">4</div>
          <h4>Strand Selection</h4>
          <p>Finalize your strand with guidance from the admission team.</p>
        </div>
        <div class="step-card fade-up">
          <div class="step-num">5</div>
          <h4>Enrollment + Orientation</h4>
          <p>Complete enrollment and attend the SHS orientation program.</p>
        </div>
      </div>
    </div>
    <div id="tab-basic" class="tab-panel" role="tabpanel">
      <div class="admission-steps">
        <div class="step-card fade-up">
          <div class="step-num">1</div>
          <h4>Application Form</h4>
          <p>Fill out the basic education application form at the admissions office.</p>
        </div>
        <div class="step-card fade-up">
          <div class="step-num">2</div>
          <h4>Required Documents</h4>
          <p>Prepare birth certificate, report cards, and academic records.</p>
        </div>
        <div class="step-card fade-up">
          <div class="step-num">3</div>
          <h4>Assessment Test</h4>
          <p>Participate in a grade-appropriate assessment for placement.</p>
        </div>
        <div class="step-card fade-up">
          <div class="step-num">4</div>
          <h4>Interview</h4>
          <p>Attend a short interview with the student and parent/guardian.</p>
        </div>
        <div class="step-card fade-up">
          <div class="step-num">5</div>
          <h4>Enrollment</h4>
          <p>Complete enrollment and secure your slot for the school year.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══ CTA BANNER ═══ -->
<div class="cta-banner">
  <div class="container" style="position:relative;z-index:2;">
    <h2>Your Education Starts Here — <br>and It's Free.</h2>
    <p>Join thousands of students who chose SEAIT for world-class education without the tuition burden. Committed to your total development.</p>
    <div class="cta-banner-actions">
      <a href="login.php" class="btn btn-white btn-lg" id="cta-login-btn">🔑 Log In to Portal</a>
      <a href="#contact" class="btn btn-outline-white btn-lg" id="cta-contact-btn">📞 Contact Admissions</a>
    </div>
  </div>
</div>

<!-- ═══ CONTACT ═══ -->
<section class="contact" id="contact" aria-labelledby="contact-heading">
  <div class="container">
    <div class="section-eyebrow fade-up"><span class="badge">Get in Touch</span></div>
    <h2 class="section-title fade-up" id="contact-heading">We're Here to Help</h2>
    <p class="section-sub fade-up">Reach out to our admissions office or visit us on campus. Our team is ready to guide you through the enrollment process.</p>
    <div class="contact-grid">
      <div class="contact-info-card fade-up">
        <h3>Contact Information</h3>
        <div class="contact-row">
          <div class="contact-icon">📍</div>
          <div class="contact-row-text">
            <strong>Main Campus</strong>
            <span>National Highway, Purok 7, Crossing Rubber, Tupi, South Cotabato, 9505, Philippines</span>
          </div>
        </div>
        <div class="contact-row">
          <div class="contact-icon">📞</div>
          <div class="contact-row-text">
            <strong>Admissions Office</strong>
            <span>(083) 226 1603</span>
          </div>
        </div>
        <div class="contact-row">
          <div class="contact-icon">📋</div>
          <div class="contact-row-text">
            <strong>Registrar's Office</strong>
            <span>(083) 226 1602</span>
          </div>
        </div>
        <div class="contact-row">
          <div class="contact-icon">🗣️</div>
          <div class="contact-row-text">
            <strong>Student Services</strong>
            <span>(083) 226-1203</span>
          </div>
        </div>
        <div class="contact-row">
          <div class="contact-icon">✉️</div>
          <div class="contact-row-text">
            <strong>Email</strong>
            <span>seaitinc@yahoo.com &nbsp;|&nbsp; info@seait.edu.ph</span>
          </div>
        </div>
      </div>
      <div class="contact-hours fade-up">
        <h3>Office Hours</h3>
        <p>Our administrative offices are open on weekdays to assist you.</p>
        <div class="hours-row">
          <span class="label">Monday – Friday</span>
          <span class="value">8:00 AM – 5:00 PM</span>
        </div>
        <div class="hours-row">
          <span class="label">Saturday</span>
          <span class="value">Closed</span>
        </div>
        <div class="hours-row">
          <span class="label">Sunday</span>
          <span class="value">Closed</span>
        </div>
        <div class="hours-row">
          <span class="label">Holidays</span>
          <span class="value">Closed</span>
        </div>
        <div class="hours-badge">Currently Open (Mon–Fri)</div>
        <div style="margin-top:28px;display:flex;flex-direction:column;gap:10px;">
          <a href="login.php" class="btn btn-primary btn-lg" id="contact-portal-btn">🎓 Access Enrollment Portal</a>
          <a href="#admission" class="btn btn-outline-white btn-lg" id="contact-apply-btn" style="border-color:rgba(255,255,255,0.3);color:#fff;">📋 View Admission Process</a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══ FOOTER ═══ -->
<footer class="footer" role="contentinfo">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-brand">
        <div class="footer-brand-logo">
          <img src="photo/logo.jpg" alt="SEAIT Logo" class="footer-logo-img" />
          <span class="footer-brand-name">SEAIT</span>
        </div>
        <p>South East Asian Institute of Technology, Inc. — committed to the total development of the student through tuition-free, high-quality education.</p>
        <span class="badge" style="background:rgba(255,140,0,0.1);color:#ffb77d;border-color:rgba(255,140,0,0.2);">Est. 2006 · Tupi, South Cotabato</span>
      </div>
      <div class="footer-col">
        <h4>Programs</h4>
        <ul>
          <li><a href="#programs">Agriculture & Fisheries</a></li>
          <li><a href="#programs">Business & Governance</a></li>
          <li><a href="#programs">Information Technology</a></li>
          <li><a href="#programs">Criminal Justice</a></li>
          <li><a href="#programs">Civil Engineering</a></li>
          <li><a href="#programs">Teacher Education</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Admission</h4>
        <ul>
          <li><a href="#admission">College Admission</a></li>
          <li><a href="#admission">Senior High School</a></li>
          <li><a href="#admission">Basic Education</a></li>
          <li><a href="#admission">TESDA Programs</a></li>
          <li><a href="#contact">Contact Admissions</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Contact</h4>
        <ul>
          <li><a href="tel:0832261603">(083) 226 1603 — Admissions</a></li>
          <li><a href="tel:0832261602">(083) 226 1602 — Registrar</a></li>
          <li><a href="mailto:seaitinc@yahoo.com">seaitinc@yahoo.com</a></li>
          <li><a href="mailto:info@seait.edu.ph">info@seait.edu.ph</a></li>
          <li><a href="login.php">Enrollment Portal</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <div class="footer-bottom-left">
        &copy; <?= date('Y') ?> South East Asian Institute of Technology, Inc. All rights reserved.
        &nbsp;·&nbsp; Tupi, South Cotabato, Philippines
      </div>
      <span class="footer-seal">🎓 CHED · DepEd · TESDA Recognized</span>
    </div>
  </div>
</footer>

<script>
  // ── Navbar scroll shadow ──
  const navbar = document.getElementById('navbar');
  window.addEventListener('scroll', () => {
    navbar.classList.toggle('scrolled', window.scrollY > 20);
  }, { passive: true });

  // ── Mobile nav ──
  const hamburger     = document.getElementById('hamburger-btn');
  const mobileNav     = document.getElementById('mobile-nav');
  const mobileClose   = document.getElementById('mobile-nav-close');
  const mobileOverlay = document.getElementById('mobile-nav-overlay');

  hamburger.addEventListener('click', () => mobileNav.classList.add('open'));
  mobileClose.addEventListener('click', () => mobileNav.classList.remove('open'));
  mobileOverlay.addEventListener('click', () => mobileNav.classList.remove('open'));

  // Close on nav link click
  mobileNav.querySelectorAll('a').forEach(a => {
    a.addEventListener('click', () => mobileNav.classList.remove('open'));
  });

  // ── Tab switching ──
  function switchTab(name) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => {
      b.classList.remove('active');
      b.setAttribute('aria-selected', 'false');
    });
    document.getElementById('tab-' + name).classList.add('active');
    document.getElementById('tab-' + name + '-btn').classList.add('active');
    document.getElementById('tab-' + name + '-btn').setAttribute('aria-selected', 'true');
  }

  // ── Scroll animations (IntersectionObserver) ──
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

  document.querySelectorAll('.fade-up, .timeline-item').forEach(el => observer.observe(el));

  // ── Active nav link on scroll ──
  const sections = document.querySelectorAll('section[id], #about');
  const navLinks = document.querySelectorAll('.nav-links a');
  const sectionObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        navLinks.forEach(a => {
          a.style.color = '';
          if (a.getAttribute('href') === '#' + entry.target.id) {
            a.style.color = 'var(--primary)';
          }
        });
      }
    });
  }, { threshold: 0.4 });
  sections.forEach(s => sectionObserver.observe(s));

  // ── Dismiss Skeleton Loader ──
  function hideLandingSkeleton() {
    const skeleton = document.getElementById('pageSkeletonLoader');
    if (skeleton) {
      skeleton.classList.add('fade-out');
      setTimeout(() => { skeleton.style.display = 'none'; }, 380);
    }
  }
  if (document.readyState === 'complete') {
    setTimeout(hideLandingSkeleton, 150);
  } else {
    window.addEventListener('load', () => setTimeout(hideLandingSkeleton, 150));
    setTimeout(hideLandingSkeleton, 1000);
  }
</script>
</body>
</html>
