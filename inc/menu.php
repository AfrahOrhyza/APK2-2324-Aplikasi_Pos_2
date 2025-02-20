<?php
@$pages = $_GET['pages'];
switch ($pages) {
    case 'tampil':
        include "../pages/master/tampil.php";
        break;
    case 'tambah':
        include "../pages/master/tambah.php";
        break;
    case 'profile':
        include "../pages/master/profile.php";
        break;
    case 'setting':
        include "../pages/master/setting.php";
        break;
    case 'print':
        include "../pages/master/print.php";
        break;
    case 'dashboard':
        include "../pages/master/dashboard.php";
        break;
    case 'user_admin':
        include "../pages/user admin/user_admin.php";
        break;
    case 'user_petugas':
        include "../pages/petugas/user_petugas.php";
        break;
    case 'jabatan':
        include "../pages/jabatan/jabatan.php";
        break;
    case 'karyawan':
        include "../pages/karyawan/user_karyawan.php";
        break;
    case 'bulan':
        include "../pages/bulan/bulan.php";
        break;
    case 'tahun':
        include "../pages/tahun/tahun.php";
        break;
    case 'currency':
        include "../pages/currency/currency.php";
        break;
    case 'fasilitas':
        include "../pages/fasilitas/facility.php";
        break;
    case 'floor':
        include "../pages/floor/floor.php";
        break;
    case 'branch':
        include "../pages/branch/branch.php";
        break;
    default:
        include "../pages/master/dashboard.php";
        break;
}
