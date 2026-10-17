<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Helpdesk Teknisi — DKIS Kota Cirebon' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap">
    <style>
        :root {
            --navy: #0C2744;
            --navy-surface: #112240;
            --navy-border: #233554;
            --g: #1F6E60;
            --g-hover: #17574C;
            --gd: #0D6E63;
            --bg: #F1F5F9;
            --card-bg: #FFFFFF;
            --ink: #1E293B;
            --mut: #475569;
            --mut-light: #94A3B8;
            --line: #E2E8F0;
            --line-subtle: #F1F5F9;
            --red: #BE123C;
            --amber: #D97706;
            --blue: #2563EB;
            --shadow-sm: 0 1px 3px 0 rgba(15, 23, 42, 0.06);
            --shadow-md: 0 4px 6px -1px rgba(15, 23, 42, 0.07);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: var(--ink);
            background: var(--bg);
            line-height: 1.45;
            -webkit-font-smoothing: antialiased;
        }
        button, input, select, textarea { font: inherit; color: inherit; }
        :focus-visible { outline: 3px solid #2DD4BF; outline-offset: 2px; }

        /* Top Header */
        .nv {
            position: sticky;
            top: 0;
            z-index: 30;
            background: var(--navy);
            color: #fff;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 16px;
            gap: 12px;
        }
        .lg { display: flex; align-items: center; gap: 10px; }
        .logo-badge {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: #1D3F6E;
            border: 1px solid #3B6AA8;
            display: grid;
            place-items: center;
            font-weight: 700;
            font-size: 12px;
            color: #BFDBFE;
            text-decoration: none;
        }
        .brand-text h1 { margin: 0; font-size: 16px; font-weight: 700; color: #fff; }
        .brand-text h1 span { font-weight: 400; color: #CBD5E1; }
        .brand-text .sub-text { display: none; }

        /* Navigation Links */
        .links { display: none; gap: 4px; }
        .links a {
            min-height: 40px;
            padding: 0 14px;
            border-radius: 10px;
            background: none;
            color: #CBD5E1;
            font-weight: 600;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.15s ease;
        }
        .links a:hover { background: rgba(255, 255, 255, 0.12); color: #fff; }
        .links a.on {
            background: rgba(255, 255, 255, 0.12);
            color: #fff;
        }

        /* User Capsule */
        .user-capsule {
            display: flex;
            align-items: center;
            gap: 9px;
            min-height: 42px;
            padding: 3px 8px 3px 4px;
            border: 0;
            border-radius: 10px;
            background: none;
            color: #fff;
            cursor: pointer;
            transition: background 0.15s;
        }
        .user-capsule:hover, .user-capsule[aria-expanded="true"] { background: rgba(255, 255, 255, 0.12); }
        .av {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #475569;
            border: 1px solid rgba(255, 255, 255, 0.2);
            display: grid;
            place-items: center;
            font-size: 11px;
            font-weight: 700;
            color: #fff;
            flex-shrink: 0;
        }
        .user-meta {
            display: none;
            flex-direction: column;
            text-align: left;
            line-height: 1.25;
        }
        .user-name {
            font-size: 13px;
            font-weight: 600;
            color: #fff;
            white-space: nowrap;
        }
        .user-dept {
            font-size: 11px;
            font-weight: 500;
            color: #94A3B8;
            white-space: nowrap;
        }
        .user-arrow {
            display: none;
            margin-left: 2px;
            flex-shrink: 0;
            transition: transform 0.2s;
        }
        .user-capsule[aria-expanded="true"] .user-arrow {
            transform: rotate(180deg);
        }

        .bell {
            position: relative;
            width: 44px;
            height: 44px;
            border: 0;
            background: none;
            color: #CBD5E1;
            display: grid;
            place-items: center;
            cursor: pointer;
            border-radius: 10px;
        }
        .bell:hover { background: rgba(255, 255, 255, 0.12); color: #fff; }
        .bell i {
            position: absolute;
            top: 10px;
            right: 11px;
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: #EF4444;
            border: 2px solid var(--navy);
        }

        /* Notification Popover Panel */
        .notif-panel {
            position: absolute;
            right: 0;
            top: 50px;
            width: 330px;
            max-width: 90vw;
            background: #fff;
            color: var(--ink);
            border: 1px solid var(--line);
            border-radius: 16px;
            box-shadow: 0 16px 36px rgba(15, 23, 42, 0.22);
            z-index: 45;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .notif-panel[hidden] { display: none; }
        .notif-header {
            padding: 12px 14px;
            background: #F8FAFC;
            border-bottom: 1px solid var(--line);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .notif-count-badge {
            background: #EF4444;
            color: #fff;
            font-size: 10px;
            font-weight: 800;
            padding: 2px 7px;
            border-radius: 999px;
        }
        .notif-mark-read {
            border: 0;
            background: none;
            font-size: 11px;
            font-weight: 600;
            color: var(--g);
            cursor: pointer;
            padding: 2px 4px;
        }
        .notif-mark-read:hover { text-decoration: underline; }
        .notif-list {
            max-height: 340px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        .notif-item {
            padding: 12px 14px;
            border-bottom: 1px solid #F1F5F9;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            gap: 4px;
            transition: background 0.15s ease;
        }
        .notif-item:hover { background: #F8FAFC; }
        .notif-item:last-child { border-bottom: 0; }
        .notif-item-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 6px;
        }
        .notif-item-title {
            font-size: 12px;
            font-weight: 700;
            color: var(--ink);
        }
        .notif-item-time {
            font-size: 10.5px;
            color: var(--mut);
            white-space: nowrap;
        }
        .notif-item-msg {
            font-size: 11.5px;
            color: var(--mut);
            line-height: 1.35;
        }

        .acct { position: relative; }
        .menu {
            position: absolute;
            right: 0;
            top: 50px;
            width: 250px;
            background: #fff;
            color: var(--ink);
            border: 1px solid var(--line);
            border-radius: 14px;
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.18);
            padding: 6px;
            z-index: 40;
        }
        .menu[hidden] { display: none; }
        .menu .mh {
            padding: 10px 12px;
            border-bottom: 1px solid var(--line);
            font-size: 13px;
            margin-bottom: 4px;
        }
        .menu .mh b { font-size: 14px; color: var(--ink); display: block; }
        .menu .mh .bidang-tag {
            display: inline-flex;
            margin-top: 4px;
            font-size: 11px;
            font-weight: 600;
            color: #0F766E;
            background: #CCFBF1;
            padding: 2px 8px;
            border-radius: 999px;
        }
        .menu a, .menu button {
            display: flex;
            width: 100%;
            min-height: 42px;
            align-items: center;
            padding: 0 12px;
            border: 0;
            border-radius: 8px;
            background: none;
            text-align: left;
            cursor: pointer;
            font-size: 13.5px;
            font-weight: 500;
            color: var(--ink);
            text-decoration: none;
            gap: 10px;
        }
        .menu a:hover, .menu button:hover { background: #F1F5F9; color: var(--g); }

        /* Main Container & Layout Structure */
        main {
            max-width: 1180px;
            margin: 0 auto;
            padding: 16px 16px 40px;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .v {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        /* Identity Banner - Hidden on mobile, shown on desktop */
        .dept-banner {
            display: none;
        }
        .dept-title { display: flex; align-items: center; gap: 16px; }
        .dept-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.2);
            display: grid;
            place-items: center;
            font-size: 22px;
        }
        .dept-title h2 { margin: 0; font-size: 17px; font-weight: 800; color: #fff; letter-spacing: -0.01em; }
        .dept-title p { margin: 2px 0 0; font-size: 12.5px; color: #CBD5E1; font-weight: 500; }
        .dept-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(13, 148, 136, 0.25);
            border: 1px solid #2DD4BF;
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            color: #5EEAD4;
        }

        /* Stat Cards */
        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin: 0;
        }
        .sk {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 8px 4px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 2px;
        }
        .sk .h { display: block; }
        .sk .l { font-size: 11px; font-weight: 600; color: var(--mut); }
        .sk b { font-size: 22px; font-weight: 800; line-height: 1.1; letter-spacing: -0.02em; }
        .sk .ico, .sk .sub { display: none; }
        .sk.warn { border-color: #FECDD3; }

        /* Filter Pills */
        .pills {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding-bottom: 2px;
            margin: 0;
            scrollbar-width: none;
        }
        .pills::-webkit-scrollbar { display: none; }
        .pill {
            flex: none;
            min-height: 44px;
            padding: 0 12px;
            border-radius: 12px;
            border: 1px solid var(--line);
            background: #fff;
            font-weight: 600;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            cursor: pointer;
            text-decoration: none;
            color: var(--ink);
        }
        .pill:hover { background: #F8FAFC; }
        .pill[aria-pressed=true], .pill.active {
            border-color: var(--g);
            background: #F0FDF9;
            color: var(--ink);
        }
        .pill em { font-style: normal; color: #64748B; font-weight: 400; }
        .dot { width: 7px; height: 7px; border-radius: 50%; display: inline-block; }

        /* Tools Search & Sort */
        .tools {
            display: flex;
            gap: 8px;
            flex-wrap: nowrap;
            align-items: center;
            margin: 0;
        }
        .in, select {
            min-height: 44px;
            border: 1px solid #CBD5E1;
            border-radius: 10px;
            padding: 0 12px;
            background: #fff;
            width: 100%;
            font-size: 13px;
        }
        .in:focus, select:focus { border-color: var(--g); outline: none; }
        .tools .in { flex: 1; min-width: 140px; }
        .tools select { width: auto; flex-shrink: 0; }
        textarea.in { min-height: 76px; padding: 10px 12px; }

        /* Ticket Cards */
        .list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin: 0;
        }
        .tk {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 14px;
            display: grid;
            gap: 10px;
        }
        .bd {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 6px;
            font-size: 12px;
            color: var(--mut);
        }
        .code {
            background: #F1F5F9;
            border: 1px solid var(--line);
            padding: 2px 8px;
            border-radius: 6px;
            font: 700 11px ui-monospace, monospace;
            color: var(--ink);
        }
        .tg {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 2px 9px;
            border-radius: 999px;
            border: 1px solid;
            font-size: 11px;
            font-weight: 700;
        }
        .tg-cat {
            font-size: 12px;
            color: var(--mut);
            background: none;
            border: none;
            padding: 0;
            font-weight: 400;
        }
        .RED { background: #FFF1F2; color: #BE123C; border-color: #FECDD3; }
        .AMBER { background: #FFFBEB; color: #92400E; border-color: #FDE68A; }
        .GREEN { background: #ECFDF5; color: #065F46; border-color: #A7F3D0; }
        .CLEAR { background: #F1F5F9; color: #475569; border-color: #E2E8F0; }

        .baru { background: #DBEAFE; color: #1D4ED8; border-color: #BFDBFE; }
        .kerja { background: #FEF3C7; color: #92400E; border-color: #FDE68A; }
        .review { background: #F3E8FF; color: #6B21A8; border-color: #E9D5FF; }
        .selesai { background: #D1FAE5; color: #065F46; border-color: #A7F3D0; }

        .tt { margin: 0; font-size: 16px; font-weight: 700; line-height: 1.3; color: #0F172A; }
        .tt.link { cursor: pointer; }
        .tt.link:hover { color: var(--g); }

        .info {
            font-size: 12px;
            color: var(--mut);
            line-height: 1.45;
        }
        .info b { color: var(--ink); }

        .sl { font-size: 12px; font-weight: 700; }
        .bar {
            height: 8px;
            border-radius: 4px;
            background: var(--line);
            overflow: hidden;
        }
        .bar i {
            display: block;
            height: 100%;
            border-radius: 4px;
        }
        .bt {
            display: flex;
            gap: 8px;
            width: 100%;
        }

        /* Buttons */
        .btn {
            flex: 1;
            min-height: 44px;
            border-radius: 10px;
            border: 1px solid var(--g);
            background: var(--g);
            color: #fff;
            font-weight: 600;
            font-size: 13px;
            padding: 0 12px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: filter 0.15s ease;
        }
        .btn:hover { filter: brightness(0.95); }
        .btn.o { background: #fff; color: var(--gd); border-color: #CBD5E1; }
        .btn.o:hover { background: #F8FAFC; border-color: var(--g); }
        .btn.danger { background: #FFF1F2; color: #BE123C; border-color: #FECDD3; }
        .btn.danger:hover { background: #FFE4E6; }
        .btn.wa { background: #0E7A3D; border-color: #0E7A3D; color: #fff; }
        .btn.wa:hover { background: #0A5E2E; }

        .empty { text-align: center; padding: 36px 16px; color: var(--mut); background: #fff; border-radius: 16px; border: 1px dashed #CBD5E1; }

        /* Dialog Modals */
        dialog {
            border: 0;
            padding: 0;
            width: 100%;
            max-width: 100%;
            height: 100%;
            max-height: 100%;
            background: var(--bg);
            color: var(--ink);
            z-index: 50;
        }
        dialog::backdrop { background: rgba(15, 23, 42, 0.6); }
        dialog[open] { display: flex; flex-direction: column; }
        .dh {
            background: #fff;
            padding: 14px 16px;
            border-bottom: 1px solid var(--line);
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: flex-start;
        }
        .db { flex: 1; overflow-y: auto; padding: 14px 16px; display: grid; gap: 14px; align-content: start; }
        .df {
            background: #fff;
            border-top: 1px solid var(--line);
            padding: 12px 16px;
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            align-items: center;
        }
        .df .btn { flex: 1; }
        .x {
            min-width: 44px;
            min-height: 44px;
            border: 0;
            background: none;
            font-size: 22px;
            cursor: pointer;
            color: var(--mut);
        }
        .x:hover { color: var(--ink); }

        /* Specific Compact Modal Styling for Decline/Obstacle & Forms */
        #dF, .modal-compact {
            width: calc(100% - 32px) !important;
            max-width: 520px !important;
            height: auto !important;
            max-height: 90vh !important;
            border-radius: 18px !important;
            margin: auto !important;
            background: #fff !important;
            box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.45) !important;
            border: 1px solid var(--line) !important;
            overflow: hidden !important;
        }
        #dF .dh, .modal-compact .dh {
            padding: 16px 20px;
            background: #fff;
            border-bottom: 1px solid var(--line);
        }
        #dF .db, .modal-compact .db {
            padding: 16px 20px !important;
            background: #fff !important;
            grid-template-columns: 1fr !important;
            max-width: 100% !important;
            overflow-y: auto;
        }
        #dF .df, .modal-compact .df {
            padding: 14px 20px;
            background: #F8FAFC;
            border-top: 1px solid var(--line);
        }

        .card {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 14px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .card h4, .k { margin: 0; font-size: 12px; font-weight: 700; color: var(--mut); letter-spacing: 0.03em; }
        .p { margin: 0; font-size: 13px; color: var(--mut); }
        .row { display: flex; align-items: center; justify-content: space-between; gap: 8px; }

        .col { display: flex; flex-direction: column; gap: 14px; }
        .col.r { order: -1; }
        .fl {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 13px;
            min-height: 44px;
            background: #F8FAFC;
        }
        .stp {
            border-left: 2px solid #CBD5E1;
            margin: 0 0 0 6px;
            padding: 0 0 0 18px;
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .stp li { position: relative; font-size: 13px; }
        .stp li:before {
            content: "";
            position: absolute;
            left: -25px;
            top: 5px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--g);
        }
        .stp li:last-child:before { background: #F59E0B; }
        .stp small { display: block; color: #64748B; font-size: 11.5px; margin-top: 2px; }
        .big { font-size: 30px; font-weight: 800; line-height: 1.1; }
        .note {
            background: #EFF6FF;
            border: 1px solid #BFDBFE;
            color: #1E3A8A;
            border-radius: 12px;
            padding: 12px;
            font-size: 13px;
            line-height: 1.45;
        }
        .opt { display: flex; align-items: center; gap: 10px; min-height: 44px; font-size: 14px; cursor: pointer; }
        .err { color: var(--red); font-size: 12px; min-height: 16px; margin: 0; font-weight: 600; }
        .toast {
            position: fixed;
            left: 50%;
            bottom: 24px;
            transform: translateX(-50%);
            background: #0F172A;
            color: #fff;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 13px;
            z-index: 60;
            max-width: 92vw;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .toast[hidden] { display: none; }

        /* Mobile Top Navigation Bar */
        .nav {
            position: sticky;
            top: 60px;
            left: 0;
            right: 0;
            z-index: 25;
            display: flex;
            background: #fff;
            border-bottom: 1px solid var(--line);
            height: 48px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
        }
        .nav button, .nav a {
            flex: 1;
            border: 0;
            background: none;
            font-weight: 600;
            font-size: 13px;
            color: var(--mut);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            padding: 0;
            transition: all 0.15s ease;
        }
        .nav button.on, .nav a.on {
            color: var(--g);
            box-shadow: inset 0 -3px 0 var(--g);
            font-weight: 700;
        }

        /* Profile & Chart Elements */
        .pfa {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: var(--g);
            color: #fff;
            font-weight: 700;
            font-size: 22px;
            display: grid;
            place-items: center;
            flex: none;
        }
        .seg { display: flex; border: 1px solid #CBD5E1; border-radius: 10px; overflow: hidden; background: #fff; }
        .seg button, .seg a {
            flex: 1;
            min-height: 44px;
            border: 0;
            background: #fff;
            font-weight: 600;
            font-size: 13px;
            color: var(--mut);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }
        .seg button[aria-pressed=true], .seg a.active { background: var(--g); color: #fff; }
        .chart { display: flex; align-items: flex-end; gap: 10px; height: 170px; padding-top: 10px; }
        .chart div { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; gap: 5px; font-size: 11px; color: var(--mut); }
        .chart i { display: block; width: 100%; max-width: 48px; border-radius: 6px 6px 0 0; background: #8FBFB4; transition: height 0.3s; }
        .chart b { color: var(--ink); font-size: 12px; }
        .rr { display: grid; grid-template-columns: 60px 1fr 24px; align-items: center; gap: 8px; font-size: 12px; color: var(--mut); }
        .kv { display: flex; justify-content: space-between; align-items: center; gap: 12px; font-size: 13px; padding: 10px 0; border-bottom: 1px solid var(--line); color: var(--mut); }
        .kv:last-child { border: 0; }
        .kv b { color: var(--ink); }
        .g2 { display: grid; gap: 14px; }
        .stars { color: #B45309; font-weight: 700; letter-spacing: 2px; }
        blockquote { margin: 0; font-size: 13px; color: var(--mut); border-left: 3px solid var(--line); padding-left: 10px; }

        /* Desktop Media Query (>= 760px) */
        @media(min-width: 760px) {
            .links { display: flex; }
            .user-meta { display: flex; }
            .user-arrow { display: block; }
            .nav { display: none; }
            .brand-text .sub-text { display: block; font-size: 11px; font-weight: 500; color: #94A3B8; line-height: 1.2; }
            .dept-banner {
                display: flex;
                background: linear-gradient(135deg, #0C2744, #1E3A8A);
                border-radius: 16px;
                padding: 18px 24px;
                color: #fff;
                align-items: center;
                justify-content: space-between;
                flex-wrap: wrap;
                gap: 16px;
                box-shadow: var(--shadow-sm);
                margin: 0;
            }
            main { padding: 24px 32px 40px; gap: 20px; }
            .nv { padding: 0 32px; }
            .stats { gap: 16px; }
            .sk {
                padding: 16px 18px;
                align-items: flex-start;
                text-align: left;
                gap: 6px;
                border-radius: 14px;
            }
            .sk .h { display: flex; align-items: center; gap: 10px; width: 100%; }
            .sk .ico { display: grid; place-items: center; width: 32px; height: 32px; border-radius: 8px; font-size: 15px; font-weight: 800; }
            .sk .sub { display: block; font-size: 12px; color: var(--mut); }
            .sk .l { font-size: 13px; }
            .sk b { font-size: 30px; }

            .tk {
                grid-template-columns: 1fr 280px;
                align-items: center;
                gap: 20px;
                padding: 18px 20px;
            }
            .toast { bottom: 24px; }
            dialog {
                max-width: 1080px;
                height: auto;
                max-height: 92vh;
                margin: auto;
                border-radius: 20px;
                overflow: hidden;
            }
            #dF, .modal-compact {
                max-width: 520px !important;
                height: auto !important;
            }
            .db { grid-template-columns: 2fr 1fr; padding: 20px 24px; }
            .col.r { order: 0; }
            .g2 { grid-template-columns: 1.4fr 1fr; }
            .g2.b { grid-template-columns: 1fr 1fr; }
            .df .btn { flex: none; padding: 0 20px; }
        }
    </style>
</head>
<body>
    @php
        $authUser = Auth::user();
        $userName = $authUser->nama ?? $authUser->name ?? 'Teknisi DKIS';
        $userBidang = $authUser->bidang?->nama_bidang ?? 'Bidang Infrastruktur TIK';
        $bidangKode = $authUser->bidang?->kode ?? 'ITIK';
        $userRole = 'Lead Teknisi Lapangan';
        $userNip = $authUser->nip ?? '19920315 202012 2 004';
        
        $words = explode(' ', trim($userName));
        $initials = '';
        foreach(array_slice($words, 0, 2) as $w) {
            $initials .= strtoupper(substr($w, 0, 1));
        }
        $currentRoute = request()->route()->getName();
    @endphp

    <header class="nv">
        <div class="lg">
            <a href="{{ route('teknisi.tugas.index') }}" class="logo-badge" title="DKIS Kota Cirebon">DK</a>
            <div class="brand-text">
                <h1>DKIS Cirebon <span>| Helpdesk</span></h1>
                <span class="sub-text">Portal Penugasan Teknisi</span>
            </div>
        </div>

        <nav class="links" aria-label="Menu utama">
            <a href="{{ route('teknisi.tugas.index') }}" class="{{ str_starts_with($currentRoute, 'teknisi.tugas') ? 'on' : '' }}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
                Tugas Saya
            </a>
            <a href="{{ route('teknisi.riwayat') }}" class="{{ $currentRoute === 'teknisi.riwayat' ? 'on' : '' }}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                Riwayat Selesai
            </a>
        </nav>

        <div class="lg" style="gap: 10px;">
            <div class="notif-wrapper" style="position: relative;">
                <button class="bell" aria-label="Notifikasi SLA" id="bell" title="Notifikasi Sistem" aria-expanded="false">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9M10 21h4"/></svg>
                    <i id="bell-dot" style="display: none;"></i>
                </button>
                <div class="notif-panel" id="notif-panel" hidden>
                    <div class="notif-header">
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <span style="font-size: 15px;">🔔</span>
                            <span style="font-size: 13px; font-weight: 700; color: var(--ink);">Notifikasi &amp; SLA</span>
                            <span class="notif-count-badge" id="notif-badge-count">0</span>
                        </div>
                        <button type="button" class="notif-mark-read" onclick="toggleNotif(false)">Tutup</button>
                    </div>
                    <div class="notif-list" id="notif-list">
                        <div style="padding: 20px; text-align: center; color: var(--mut); font-size: 12px;">Memuat notifikasi...</div>
                    </div>
                </div>
            </div>

            <div class="acct">
                <div class="user-capsule" id="acct" role="button" aria-haspopup="menu" aria-expanded="false" aria-controls="menu">
                    <span class="av">{{ $initials ?: 'TK' }}</span>
                    <div class="user-meta">
                        <span class="user-name">{{ $userName }}</span>
                        <span class="user-dept">{{ $bidangKode }} • Teknisi</span>
                    </div>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#94A3B8" stroke-width="2.5" class="user-arrow"><path d="M6 9l6 6 6-6"/></svg>
                </div>

                <div class="menu" id="menu" role="menu" hidden>
                    <div class="mh">
                        <b>{{ $userName }}</b>
                        <span class="bidang-tag">{{ $userBidang }}</span>
                        <div style="font-size: 11px; color: var(--mut); margin-top: 4px;">NIP: {{ $userNip }}</div>
                    </div>
                    <a role="menuitem" href="{{ route('teknisi.profil') }}">
                        <span>👤</span> Profil dan Statistik KPI
                    </a>
                    <a role="menuitem" href="{{ route('teknisi.profil') }}#ubah-password">
                        <span>🔑</span> Ubah Kata Sandi
                    </a>
                    <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                        @csrf
                        <button type="submit" role="menuitem" style="color: var(--red);">
                            <span>🚪</span> Keluar dari Sesi
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <!-- Mobile Top Navigation Bar -->
    <nav class="nav" aria-label="Menu mobile">
        <a href="{{ route('teknisi.tugas.index') }}" class="{{ str_starts_with($currentRoute, 'teknisi.tugas') ? 'on' : '' }}">
            <span>Tugas</span>
        </a>
        <a href="{{ route('teknisi.riwayat') }}" class="{{ $currentRoute === 'teknisi.riwayat' ? 'on' : '' }}">
            <span>Riwayat</span>
        </a>
        <button id="nb" type="button">
            <span>Notifikasi</span>
        </button>
        <a href="{{ route('teknisi.profil') }}" class="{{ $currentRoute === 'teknisi.profil' ? 'on' : '' }}">
            <span>Profil</span>
        </a>
    </nav>

    <main>
        <!-- Identity Department Banner -->
        <div class="dept-banner">
            <div class="dept-title">
                <div class="dept-icon">🏢</div>
                <div>
                    <h2>{{ $userBidang }}</h2>
                    <p>Dinas Komunikasi, Informatika dan Statistik Kota Cirebon • Penugasan Lapangan Resmi</p>
                </div>
            </div>
            <div class="dept-badge">
                <span style="width: 7px; height: 7px; border-radius: 50%; background: #2DD4BF;"></span>
                {{ $userRole }} • Aktif Bertugas
            </div>
        </div>

        @yield('content')
    </main>

    <!-- Global Toast Notification -->
    <div class="toast" id="toast" role="status" aria-live="polite" hidden></div>

    <script>
        const $ = s => document.querySelector(s);
        const $$ = s => [...document.querySelectorAll(s)];
        const esc = s => String(s ?? '').replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));

        function toast(m) {
            const e = $('#toast');
            if (!e) return;
            e.innerHTML = `<span>🔔</span> <span>${m}</span>`;
            e.hidden = false;
            clearTimeout(toast.t);
            toast.t = setTimeout(() => e.hidden = true, 3600);
        }

        const mn = $('#menu'), ac = $('#acct');
        function cm() {
            if (mn) mn.hidden = true;
            if (ac) ac.setAttribute('aria-expanded', 'false');
        }
        if (ac) {
            ac.onclick = e => {
                e.stopPropagation();
                const o = mn.hidden;
                mn.hidden = !o;
                ac.setAttribute('aria-expanded', String(o));
            };
        }
        const np = $('#notif-panel'), bell = $('#bell'), bellDot = $('#bell-dot'), notifCount = $('#notif-badge-count'), notifList = $('#notif-list');

        function toggleNotif(show) {
            if (!np) return;
            const willShow = typeof show === 'boolean' ? show : np.hidden;
            np.hidden = !willShow;
            if (bell) bell.setAttribute('aria-expanded', String(willShow));
            if (willShow && mn) mn.hidden = true;
        }

        if (bell) {
            bell.onclick = e => {
                e.stopPropagation();
                toggleNotif();
            };
        }

        async function loadNotifications() {
            try {
                const res = await fetch('{{ route("teknisi.notifikasi") }}', {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data && data.items) {
                    if (notifCount) notifCount.textContent = data.unread_count || 0;
                    if (bellDot) bellDot.style.display = (data.unread_count > 0) ? 'block' : 'none';
                    if (notifList) {
                        if (!data.items.length) {
                            notifList.innerHTML = '<div style="padding:20px;text-align:center;color:var(--mut);font-size:12px">Tidak ada notifikasi baru.</div>';
                        } else {
                            notifList.innerHTML = data.items.map(item => `
                                <a href="${item.url || '#'}" class="notif-item">
                                    <div class="notif-item-top">
                                        <span class="notif-item-title">${esc(item.title)}</span>
                                        <span class="notif-item-time">${esc(item.time)}</span>
                                    </div>
                                    <div class="notif-item-msg">${esc(item.message)}</div>
                                </a>
                            `).join('');
                        }
                    }
                }
            } catch (e) {
                console.warn('Failed to load notifications', e);
            }
        }
        loadNotifications();

        document.addEventListener('click', e => {
            if (!e.target.closest('.acct')) cm();
            if (!e.target.closest('.notif-wrapper') && np && !np.hidden) toggleNotif(false);
        });
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                if (mn && !mn.hidden) { cm(); if (ac) ac.focus(); }
                if (np && !np.hidden) toggleNotif(false);
            }
        });

        @if(session('success'))
            toast("{{ session('success') }}");
        @endif
        @if(session('error'))
            toast("{{ session('error') }}");
        @endif

        // Mencegah error '419 Page Expired': Refresh session / CSRF di latar belakang setiap 10 menit
        setInterval(function() {
            fetch('{{ route("teknisi.notifikasi") }}', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            }).catch(() => {});
        }, 10 * 60 * 1000);
    </script>

    @yield('scripts')
</body>
</html>
