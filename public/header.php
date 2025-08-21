<?php
require_once __DIR__ . '/../lib/auth.php';
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Rayterton Productivity Tracker</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

    body {
      font-family: 'Inter', sans-serif;
    }

    .active-nav {
      position: relative;
      color: #4f46e5;
    }

    .active-nav::after {
      content: '';
      position: absolute;
      bottom: 1px;
      left: 0;
      width: 100%;
      height: 2px;
      background-color: #4f46e5;
      border-radius: 2px;
    }
  </style>
</head>

<body class="bg-gray-50 text-gray-800">
  <!-- Header/Navigation -->
  <header class="bg-white shadow-sm sticky top-0 z-10">
    <div class="container mx-auto px-4 py-3">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center justify-between flex-1 lg:w-auto">
          <h1 class="text-lg md:text-xl font-bold text-indigo-700">
            <span class="hidden sm:inline">Rayterton</span> Productivity Tracker
          </h1>
          <!-- humburger menu -->
          <div class="lg:hidden flex">
            <button id="menu-btn" class="text-gray-600 hover:text-indigo-600 focus:outline-none">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M4 6h16M4 12h16M4 18h16"></path>
              </svg>
            </button>
          </div>
        </div>

        <?php if (isset($_SESSION['user'])): ?>
          <!-- menu dekstop -->
          <nav class="hidden lg:flex items-center overflow-x-auto pb-2 md:pb-0">
            <div class="flex space-x-1 md:space-x-4">
              <a href="dashboard.php"
                class="px-3 py-2 text-sm font-medium rounded-md transition-all hover:text-indigo-600 <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active-nav' : 'text-gray-600 hover:bg-gray-100' ?>">
                Dashboard
              </a>
              <a href="attendance.php"
                class="px-3 py-2 text-sm font-medium rounded-md transition-all hover:text-indigo-600 <?= basename($_SERVER['PHP_SELF']) === 'attendance.php' ? 'active-nav' : 'text-gray-600 hover:bg-gray-100' ?>">
                Absen
              </a>
              <a href="report_form.php"
                class="px-3 py-2 text-sm font-medium rounded-md transition-all hover:text-indigo-600 <?= basename($_SERVER['PHP_SELF']) === 'report_form.php' ? 'active-nav' : 'text-gray-600 hover:bg-gray-100' ?>">
                Report Input
              </a>
              <a href="reports_my.php"
                class="px-3 py-2 text-sm font-medium rounded-md transition-all hover:text-indigo-600 <?= basename($_SERVER['PHP_SELF']) === 'reports_my.php' ? 'active-nav' : 'text-gray-600 hover:bg-gray-100' ?>">
                My Report
              </a>
              <a href="profil.php"
                class="px-3 py-2 text-sm font-medium rounded-md transition-all hover:text-indigo-600 <?= basename($_SERVER['PHP_SELF']) === 'profil.php' ? 'active-nav' : 'text-gray-600 hover:bg-gray-100' ?>">
                My Profil
              </a>
              <?php if ($_SESSION['user']['role'] === 'admin'): ?>
                <a href="admin_reports.php"
                  class="px-3 py-2 text-sm font-medium rounded-md transition-all hover:text-indigo-600 <?= basename($_SERVER['PHP_SELF']) === 'admin_reports.php' ? 'active-nav' : 'text-gray-600 hover:bg-gray-100' ?>">
                  Admin
                </a>
              <?php endif; ?>
              <?php if ($_SESSION['user']['role'] === 'admin'): ?>
                <a href="admin_attendance.php"
                  class="px-3 py-2 text-sm font-medium rounded-md transition-all hover:text-indigo-600 <?= basename($_SERVER['PHP_SELF']) === 'admin_attendance.php' ? 'active-nav' : 'text-gray-600 hover:bg-gray-100' ?>">
                  Absen Report
                </a>
              <?php endif; ?>
              <a href="logout.php"
                class="px-3 py-2 text-sm font-medium text-white rounded-md bg-red-500 hover:bg-red-700 transition-all">
                Logout
              </a>
            </div>
          </nav>
        <?php endif; ?>
      </div>
      <?php if (isset($_SESSION['user'])): ?>
        <!-- menu mobile -->
        <nav id="mobile-menu" class="hidden lg:hidden mt-3 space-y-2">
          <div class="flex flex-col">
            <a href="dashboard.php" class="px-3 py-2 rounded-md hover:bg-gray-100 <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active-nav' : 'text-gray-600' ?>">Dashboard</a>
            <a href="attendance.php" class="px-3 py-2 rounded-md hover:bg-gray-100 <?= basename($_SERVER['PHP_SELF']) === 'attendance.php' ? 'active-nav' : 'text-gray-600' ?>">Absen</a>
            <a href="report_form.php" class="px-3 py-2 rounded-md hover:bg-gray-100 <?= basename($_SERVER['PHP_SELF']) === 'report_form.php' ? 'active-nav' : 'text-gray-600' ?>">Input Laporan</a>
            <a href="reports_my.php" class="px-3 py-2 rounded-md hover:bg-gray-100 <?= basename($_SERVER['PHP_SELF']) === 'reports_my.php' ? 'active-nav' : 'text-gray-600' ?>">Laporan Saya</a>
            <a href="profil.php" class="px-3 py-2 rounded-md hover:bg-gray-100 <?= basename($_SERVER['PHP_SELF']) === 'profil.php' ? 'active-nav' : 'text-gray-600' ?>">My Profil</a>
            <?php if ($_SESSION['user']['role'] === 'admin'): ?>
              <a href="admin_reports.php" class="px-3 py-2 rounded-md hover:bg-gray-100 <?= basename($_SERVER['PHP_SELF']) === 'admin_reports.php' ? 'active-nav' : 'text-gray-600' ?>">Admin</a>
              <a href="admin_attendance.php" class="px-3 py-2 rounded-md hover:bg-gray-100 <?= basename($_SERVER['PHP_SELF']) === 'admin_attendance.php' ? 'active-nav' : 'text-gray-600' ?>">Laporan Absen</a>
            <?php endif; ?>
            <a href="logout.php" class="px-3 py-2 rounded-md hover:bg-gray-100 text-red-500">Logout</a>
          </div>
        </nav>
      <?php endif; ?>
    </div>
  </header>

  <!-- Main Content -->
  <main class="container mx-auto px-4">
    <?php // Konten utama akan dimasukkan di sini 
    ?>

    <script>
      // navbar responsive
      document.getElementById('menu-btn').addEventListener('click', function() {
        document.getElementById('mobile-menu').classList.toggle('hidden');
      });
    </script>