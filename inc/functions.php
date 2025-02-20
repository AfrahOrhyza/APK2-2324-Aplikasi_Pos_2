<?php
//set waktu
date_default_timezone_set('Asia/Jakarta');
$tgl = date('Y-m-d H:i:s');

//koneksi database
$HOSTNAME = "localhost";
$DATABASE = "db_apartement_fix";
$USERNAME = "root";
$PASSWORD = "";


$KONEKSI = mysqli_connect($HOSTNAME, $USERNAME, $PASSWORD, $DATABASE);

if (!$KONEKSI) {
    die("koneksi database error" . mysqli_connect_error($KONEKSI));
}

//fungsi autonumber
function autonumber($tabel, $kolom, $lebar = 0, $awalan)
{
    global $KONEKSI;

    $auto = mysqli_query($KONEKSI, "SELECT $kolom FROM
    $tabel ORDER BY $kolom desc limit 1") or die(mysqli_error($KONEKSI));
    $jumlah_record = mysqli_num_rows($auto);

    if ($jumlah_record == 0) {
        $nomor = 1;
    } else {
        $row = mysqli_fetch_array($auto);
        $nomor = intval(substr($row[0], strlen($awalan))) + 1;
    }

    if ($lebar > 0) {
        $angka = $awalan . str_pad($nomor, $lebar, "0", STR_PAD_LEFT);
    } else {
        $angka = $awalan . $nomor;
    }
    return $angka;
}
//echo autonumber ("tbl_users", "id_user",3, "USR");




//fungsi register
function registrasi($data)
{
    global $KONEKSI;
    global $tgl;

    $id_user = stripslashes($data['id_user']);
    $nama = stripslashes($data['nama']); //untuk cek form register dari nama
    $email = strtolower(stripslashes($data['email'])); //memastikan form register mengirim input email berupa huruf kecil semua
    $password = mysqli_real_escape_string($KONEKSI, $data['password']);
    $password2 = mysqli_real_escape_string($KONEKSI, $data['password2']);


    //echo $nama."|".$email."|".$password."|".$password2;

    //cek email yang di input belum di database 

    $result = mysqli_query($KONEKSI, "SELECT email from tbl_users WHERE email='$email'");
    //var_dump($result);

    if (mysqli_fetch_assoc($result)) {
        echo "<script>
    alert('email sudah ada di database :3');
    </script>";
        return false;
    }

    //cek konfirmasi password 
    if ($password !==   $password2) {
        echo "<script>
    alert('konfirmasi password tidak sesuai !!');
            document.location.href='register.php';
    </script>";
        return false;
    }

    //enkripsi password yang akan masukkan ke database 
    $password_hash = password_hash($password, PASSWORD_DEFAULT); // menggunakan algoritma dari hash 
    //var_dump($password_hash);

    //ambil id_tipe_user yg ada di tbl_tipe_user

    $tipe_user = "SELECT * FROM tbl_tipe_user WHERE tipe_user='Admin' ";
    $hasil = mysqli_query($KONEKSI, $tipe_user);
    $row = mysqli_fetch_assoc($hasil);
    $id = $row['id_tipe_user'];

    //tambahkan user baru ke tbl_users
    $sql_users = "INSERT INTO tbl_users SET 
    id_user = '$id_user',
    role = '$id',
    email = '$email',
    password = '$password_hash',
    create_at = '$tgl'";

    mysqli_query($KONEKSI, $sql_users) or die("gagal menambahkan user" . mysqli_error($KONEKSI));

    //tambahkan user baru ke tbl_admin
    $sql_admin  = "INSERT INTO tbl_admin SET
nama_admin = '$nama',
id_user = '$id_user',
create_at = '$tgl' ";

    mysqli_query($KONEKSI, $sql_admin) or die("gagal menambahkan user" . mysqli_error($KONEKSI));


    echo "<script>
        document.location.href='login.php';
</script>";

    return mysqli_affected_rows($KONEKSI);
}

//fungsi tampil data 
function tampil($DATA)
{
    global $KONEKSI;

    $HASIL = mysqli_query($KONEKSI, $DATA);
    $data = []; //menyiapkan variabel/wadah yg masi kosong untuk nantinya akan kita gunakan untuk menyimpan data yg kita query/panggil dari database

    while ($row = mysqli_fetch_assoc($HASIL)) {
        $data[] = $row;
    }
    return $data; //kita kembalikan nilainya, di munculkan 

}

//fungsi tambah data admin
function tambah_admin($DATA)
{
    global $KONEKSI;
    global $tgl;

    $id_admin   = stripslashes($_POST['id_admin']);
    $nama_admin = stripslashes($_POST['nama_admin']);
    $email      = strtolower(stripslashes($_POST['email']));
    $telepon    = stripslashes($_POST['telepon']);
    $role       = stripslashes($_POST['role']);
    $password   = mysqli_real_escape_string($KONEKSI, $_POST['password']);
    $password2  = mysqli_real_escape_string($KONEKSI, $_POST['password2']);

    //cek email yg di daftar apakah sudah dipakai atau belum 
    $result = mysqli_query($KONEKSI, "SELECT email FROM tbl_users WHERE email = '$email' ");

    if (mysqli_fetch_assoc($result)) {
        echo "<script>
    alert('email sudah ada di database :3');
    document.location.href='?pages=user_admin';
    </script>";

        return false;
    }

    //cek konfirmasi password
    if ($password !== $password2) {
        echo "<script>
    alert('konfirmasi email yg di input tidak sama !!!');
    document.location.href='?pages=user_admin';
    </script>";
        return false;
    }

    //kita lakukan enkipsi password yang dia input
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    //pastikan data gambar ke upload

    $gambar_foto = upload_file();

    //jika tidak di upload foto proses kita hentikan
    if (!$gambar_foto) {
        return false;
    }

    //tambahkan data user baru ke tbl_users
    $sql_user = "INSERT INTO tbl_users SET 
id_user = '$id_admin',
email = '$email',
password = '$password_hash',
role = '$role',
create_at = '$tgl' ";

    mysqli_query($KONEKSI, $sql_user) or die("gagal menambahkan user baru") .  mysqli_error($KONEKSI);

    //tambah data user baru ke tbl admin
    $sql_users = "INSERT INTO tbl_admin SET 
nama_admin = '$nama_admin',
telepon_admin = '$telepon',
path_photo_admin = '$gambar_foto',
id_user = '$id_admin',
create_at = '$tgl' ";

    mysqli_query($KONEKSI, $sql_users) or die("gagal menambahkan admin baru" . mysqli_error($KONEKSI));
    return mysqli_affected_rows($KONEKSI);
}


//fungsi upload file
function upload_file()
{
    //inisialisasi elemen dari gambar
    $namaFile   = $_FILES['Photo']['name'];
    $ukuranFile = $_FILES['Photo']['size'];
    $error      = $_FILES['Photo']['error'];
    $tmpName    = $_FILES['Photo']['tmp_name'];
    $tipeFile   = $_FILES['Photo']['type'];
    $id_admin   = $_POST['id_admin'];

    //kita pastikan user upload file 
    if ($error == 4) { //4 artinya tidak ada file yg di upload 
        echo "<script>
        alert('tidak ada file yang di upload')
        </script>";
        return false;
    }

    //kita pastikan validasi ekstensi file
    $ekstensiValid = ['jpg', 'jpeg', 'bmp', 'png'];
    $ekstensiFile = explode('.', $namaFile);
    $ekstensiFile = strtolower(end($ekstensiFile));

    if (!in_array($ekstensiFile, $ekstensiValid)) {
        echo "<script>
        alert('file yg anda upload bukan gambar')
        </script>";
        return false;
    }

    //kita validasi ukuran maksimal foto
    if ($ukuranFile > 1 * 1024 * 1024) {
        echo "<script>
        alert('ukuran file gaboleh lebih dari 1M')
        </script>";
        return false;
    }

    //membuat nama file yang baru yg unik 
    $id_random = uniqid();
    $namaFileBaru = $id_admin . "_" . $id_random . "." . $ekstensiFile;


    $target = '../images/user/';
    $file_path = $target . $namaFileBaru;

    //kita cek apakah ada nama baru sudah tebentuk, jika ada lgsg upload file
    echo "menyalin file ke : " . $file_path;

    if (move_uploaded_file($tmpName, $file_path)) {
        echo "<script>
        alert('file berhasil di upload')
        </script>";
        return $namaFileBaru;
    } else {
        echo "<script>
        alert('gagal melakukan upload')
        </script>";
        return false;
    }
}

//fungsi edit admin user admin
function edit_admin($data)
{
    global $KONEKSI;
    global $tgl;

    $id_admin = htmlspecialchars($data['id_admin']);
    $nama_admin = htmlspecialchars($data['nama_admin']);
    $email = htmlspecialchars($data['email']);
    $telepon = htmlspecialchars($data['telepon']);
    $foto_lama = htmlspecialchars($data['photo_db']);

    $target = '../images/user/'; //lokasi foto lama 
    $cek_file_lama = $target . $foto_lama;

    //cek apakah ada file baru yg di upload oleh server
    if (isset($_FILES['Photo']) && $_FILES['Photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        //jika ada file gambar baru yg di upload
        $foto_edit = upload_file();

        //kita pastikan nama file baru ter upload (debugging)
        echo "File baru: " . $foto_edit . "berhasil di upload";

        //kita pastikan file lama di hapuskan (unlink)

        //cek dulu file lama di db apakah ada di folder target

        if ($foto_edit && file_exists($cek_file_lama)) {
            if (unlink($cek_file_lama)) {
                //true ==> berhasil hapus file lama
                echo "file lama berhasil di hapus";
            } else {
                echo "gagal menghapus file lama";
            }
        }
    } else {
        //jika tidak ada file baru, gunakan gambar lama
        $foto_edit = $foto_lama;
        echo "menggunakan foto lama: " . $foto_lama;
    }


    //update edit data ke tbl admin
    $sql_user = "UPDATE tbl_admin SET 
        nama_admin = '$nama_admin',
        telepon_admin = '$telepon',
        path_photo_admin = '$foto_edit',
        update_at = '$tgl' WHERE tbl_admin.id_user = '$id_admin' ";

    if (mysqli_query($KONEKSI, $sql_user)) {
        echo "<script>
        alert('data berhasil di update')
        </script>";
    } else {
        echo "<script>
        alert('gagal update data')
        </script>";
    }

    return mysqli_affected_rows($KONEKSI);
}

// fungsi hapus user admin
function hapus_admin()
{

    global $KONEKSI;
    $id_user = $_GET['id'];

    // hapus file gambar yang usernya kita hapus
    $sql = "SELECT * FROM tbl_admin WHERE id_user='$id_user' " or die("Data tidak ditemukan" . mysqli_error($KONEKSI));
    $hasil = mysqli_query($KONEKSI, $sql);
    $row = mysqli_fetch_assoc($hasil);

    $photo = $row['path_photo_admin'];
    $target = '../images/user/';

    if (!$photo == "") {
        // Jika ada maka kita hapus
        unlink($target . $photo);
    }


    // hapus data di tabel admin
    $query_admin = "DELETE FROM tbl_admin WHERE id_user='$id_user' ";
    mysqli_query($KONEKSI, $query_admin) or die("Gagal melakukan hapus data admin" . mysqli_error($KONEKSI));

    // hapus data di tabel users
    $query_user = "DELETE FROM tbl_users WHERE id_user='$id_user' ";
    mysqli_query($KONEKSI, $query_user) or die("Gagal melakukan hapus data user" . mysqli_error($KONEKSI));


    return mysqli_affected_rows($KONEKSI);
}

//fungsi upload file menggunakan parameter
function upload_file_new($data, $file, $target)
{
    //inisialisasi elemen dari foto/file
    $namaFile   = $file['Photo']['name'];
    $ukuranFile = $file['Photo']['size'];
    $error      = $file['Photo']['error'];
    $tmpName    = $file['Photo']['tmp_name'];
    $tipeFile   = $file['Photo']['type'];

    $kode  = htmlspecialchars($data['kode']);

    //debug buat element $data dan $file
    echo "<pre>";
    print_r($data); //melihat data yg akan di terima
    print_r($file); //melihat file yg akan di terima
    echo "</pre>";

    //pastikan bahwa user melakukan upload file
    if ($error == UPLOAD_ERR_NO_FILE) {
        echo "<script>alert('Tidak ada file yang di upload!');
        </script>";
        return false;
    }

    //validasi ekstensi file
    $ekstensiValid = ['jpeg', 'jpg', 'bmp', 'png'];
    $ekstensifile  = strtolower(pathinfo($namaFile, PATHINFO_EXTENSION));

    if (!in_array($ekstensifile, $ekstensiValid)) {
        echo "<script>alert('File yang anda upload bukan gambar!');
        </script>";
        return false;
    }

    //validasi ukuran gambar
    if ($ukuranFile > 1 * 1024 * 1024) {
        echo "<script>alert('Ukuran file tidak boleh dari 1MB!');
        </script>";
        return false;
    }

    //membuat nama file baru yang uniq
    $id_random = uniqid();
    $namaFileBaru = $kode . "_" . $id_random . "." . $ekstensifile;

    $file_path = $target . $namaFileBaru;


    // echo $file_path . " | ". $tmpName;
    // die;
    //cek apakah file sudah terupload
    if (move_uploaded_file($tmpName, $file_path)) {
        echo "<script>alert('Fle berhasil di upload!');
        </script>";
        return $namaFileBaru;
    } else {
        echo "<script>alert('gagal upload file!');
        </script>";
        return false;
    }
}


//fungsi tambah branch
function tambah_branch($data, $file, $target)
{
    global $KONEKSI;
    global $tgl;

    $kode_branch          = stripslashes($data['kode']);
    $nama_perusahaan      = stripslashes($data['nama_cab']);
    $alamat_perusahaan    = stripslashes($data['alamat']);
    $email_perusahaan     = stripslashes($data['email']);
    $telepon_perusahaan   = stripslashes($data['telepon']);
    $kecamatan_perusahaan = stripslashes($data['kecamatan']);
    $kota_perusahaan      = stripslashes($data['kota']);
    $provinsi_perusahaan  = stripslashes($data['provinsi']);
    $kode_pos             = stripslashes($data['kodepos']);


    echo "<pre>";
    print_r($data); //melihat data yg akan di terima
    print_r($file); //melihat file yg akan di terima
    echo "</pre>";

    //kita harus upload file
    $gambar_foto = upload_file_new($data, $file, $target);

    //kita input data ke tabel
    if ($gambar_foto) {
        //jika upload berhasil maka di lanjut jgn di insert
        $sql = "INSERT INTO tbl_branch SET 
        kode_branch          = '$kode_branch',
        nama_perusahaan      = '$nama_perusahaan',
        alamat_perusahaan    = '$alamat_perusahaan',
        email_perusahaan     = '$email_perusahaan',
        telepon_perusahaan   = '$telepon_perusahaan',
        kecamatan_perusahaan = '$kecamatan_perusahaan',
        kota_perusahaan      = '$kota_perusahaan',
        provinsi_perusahaan  = '$provinsi_perusahaan',
        path_logo            = '$gambar_foto',
        kode_pos             = '$kode_pos',
        create_at            = '$tgl' ";


        // cek apakah query berhasil apa tidak
        if (mysqli_query($KONEKSI, $sql)) {
            echo "<script>alert('Data berhasil di tambahkan!');
        </script>";
            return true;
        } else {
            echo "<script>alert('Data tidak berhasil di tambahkan! " . mysqli_error($KONEKSI) . " ');
        </script>";
            return false;
        }
    } else {
        echo "<script>
        alert('Gagal melakukan upload file');
        </script>";
        return false;
    }
}

//fungsi edit branch
function edit_branch($data, $file, $target)
{
    global $KONEKSI;
    global $tgl;

    $kode_branch          = stripslashes($data['kode']);
    $nama_perusahaan      = stripslashes($data['nama_cab']);
    $alamat_perusahaan    = stripslashes($data['alamat']);
    $email_perusahaan     = stripslashes($data['email']);
    $telepon_perusahaan   = stripslashes($data['telepon']);
    $kecamatan_perusahaan = stripslashes($data['kecamatan']);
    $kota_perusahaan      = stripslashes($data['kota']);
    $provinsi_perusahaan  = stripslashes($data['provinsi']);
    $foto_lama            = stripslashes($data['photo_db']);
    $kode_pos             = stripslashes($data['kodepos']);

    $cek_file_lama = $target . $foto_lama;

    //cek apakah ada file baru yg di upload oleh server
    if (isset($_FILES['Photo']) && $_FILES['Photo']['error'] !== UPLOAD_ERR_NO_FILE) {

        //kita harus upload file
        $gambar_foto = upload_file_new($data, $file, $target);
        echo $gambar_foto;

        //kita pastikan nama file baru ter upload (debugging)
        echo "File Baru :" . $gambar_foto . "Berhasil Di Upload";

        //kita pastikan file lama di hapuskan (unlink)

        //cek dulu file lama di db apakah ada di folder target
        if ($gambar_foto && file_exists($cek_file_lama)) {
            if (unlink($cek_file_lama)) {
                //true ==> berhasil hapus file lama
                echo "file lama berhasil di hapus";
            } else {
                echo "gagal menghapus file lama";
            }
        }
    } else {
        //jika tidak ada file baru, gunakan gambar lama
        $gambar_foto = $foto_lama;
        echo "menggunakan foto lama: " . $foto_lama;
    }


    //update edit data ke tbl_admin
    $sql_user = "UPDATE tbl_branch SET 
        nama_perusahaan      = '$nama_perusahaan ',
        alamat_perusahaan    = '$alamat_perusahaan',
        email_perusahaan     = '$email_perusahaan',
        telepon_perusahaan   = '$telepon_perusahaan',
        path_logo            = '$gambar_foto',
        kecamatan_perusahaan = '$kecamatan_perusahaan',
        kota_perusahaan      = '$kota_perusahaan',
        provinsi_perusahaan  = '$provinsi_perusahaan',
        kode_pos             = '$kode_pos',
        update_at            = '$tgl' WHERE kode_branch = '$kode_branch' ";

    if (mysqli_query($KONEKSI, $sql_user)) {
        echo "<script>
        alert('data berhasil di update')
        </script>";
    } else {
        echo "<script>
        alert('gagal update data')
        </script>";
    }

    return mysqli_affected_rows($KONEKSI);
}


// fungsi hapus branch
function hapus_branch($data, $target)
{

    global $KONEKSI;
    $kode_branch = htmlspecialchars($data['id']);
    echo $kode_branch;

    echo "<pre>";
    print_r($data); //melihat data yg akan di terima
    print_r($target); //melihat file yg akan di terima
    echo "</pre>";


    //ambil nama file gambar yg terkait degn cabang yg akan di hapus
    $query = "SELECT path_logo FROM tbl_branch WHERE kode_branch = '$kode_branch'";
    $result = mysqli_query($KONEKSI, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
        $gambar_foto = $data['path_logo'];
        //echo $gambar_foto;

        //hapus data dari database
        $deleteQuery = "DELETE FROM tbl_branch WHERE kode_branch = '$kode_branch'";
        if (mysqli_query($KONEKSI, $deleteQuery)) {
            //hapus file gambar dari folder jika ada
            if ($gambar_foto && file_exists($target . $gambar_foto)) {
                unlink($target . $gambar_foto);
            }
            echo "<script>alert('Data berhasil di hapus!');</script>";
            return true;
        } else {
            echo "<script>alert('Gagal menghapus data!');</script>";
            return false;
        }
    } else {
        echo "<script>alert('Data tidak di temukan!');</script>";
        var_dump(mysqli_num_rows($result));
        die;
        return false;
    }
}

//fungsi tambah petugas
function tambah_petugas($data, $file, $target)
{
    global $KONEKSI;
    global $tgl;

    $ID = htmlspecialchars($data['kode']);
    $nama_petugas = htmlspecialchars($data['nama_petugas']);
    $email        = htmlspecialchars($data['email']);
    $telepon      = htmlspecialchars($data['telepon']);
    $jenkel       = htmlspecialchars($data['jenkel']);
    $cabang       = htmlspecialchars($data['cabang']);
    $role         = htmlspecialchars($data['role']);
    $password     = mysqli_escape_string($KONEKSI, $data['password']);
    $password2    = mysqli_escape_string($KONEKSI, $data['password2']);

    //var_dump($_POST);
    //var_dump($_FILES);

    //die;
    //pastikan gambar terupload
    $gambar_foto = upload_file_new($data, $file, $target);

    //var_dump($gambar_foto);
    //die;

    //jika gambar tidak di upload operasi di hentikan
    if (!$gambar_foto) {
        return false;
    }

    //cek email yg di daftar apakah sudah dipakai atau belum 
    $result = mysqli_query($KONEKSI, "SELECT email FROM tbl_users WHERE email = '$email' ");

    if (mysqli_fetch_assoc($result)) {
        echo "<script>
        alert('email sudah ada di database :3');
        document.location.href='?pages=user_petugas';
        </script>";

        return false;
    }

    //cek konfirmasi password
    if ($password !== $password2) {
        echo "<script>
        alert('konfirmasi email yg di input tidak sama !!!');
        document.location.href='?pages=user_petugas';
        </script>";
        return false;
    }

    //kita lakukan enkipsi password yang dia input
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    //tambahkan data user baru ke tbl_users
    $sql_user = "INSERT INTO tbl_users SET 
    id_user = '$ID',
    email = '$email',
    password = '$password_hash',
    role = '$role',
    create_at = '$tgl' ";

    mysqli_query($KONEKSI, $sql_user) or die("gagal menambahkan user baru") .  mysqli_error($KONEKSI);

    //tambah data user baru ke tbl admin
    $sql_petugas  = "INSERT INTO tbl_petugas SET 
    nama_petugas       = '$nama_petugas',
    telepon_petugas    = '$telepon',
    path_photo_petugas = '$gambar_foto',
    id_user            = '$ID',
    jenkel             = '$jenkel',
    branch_id          = '$cabang',
    create_at          = '$tgl' ";

    var_dump($sql_petugas);
    //die;
    mysqli_query($KONEKSI, $sql_petugas) or die("gagal menambahkan admin baru" . mysqli_error($KONEKSI));

    return mysqli_affected_rows($KONEKSI);
}

//fungsi edit user petugas
function edit_petugas($data, $file, $target)
{
    global $KONEKSI;
    global $tgl;

    $id_petugas           = stripslashes($data['kode']);
    $email                = stripslashes($data['email']);
    $nama_petugas         = stripslashes($data['nama_petugas']);
    $telepon              = stripslashes($data['telepon']);
    $cabang               = stripslashes($data['cabang']);
    $jenkel               = stripslashes($data['jenkel']);
    $foto_lama            = stripslashes($data['photo_db']);

    $cek_file_lama = $target . $foto_lama;

    //cek apakah ada file baru yg di upload oleh server
    if (isset($_FILES['Photo']) && $_FILES['Photo']['error'] !== UPLOAD_ERR_NO_FILE) {

        //kita harus upload file
        $gambar_foto = upload_file_new($data, $file, $target);
        echo $gambar_foto;

        //kita pastikan nama file baru ter upload (debugging)
        echo "File Baru :" . $gambar_foto . "Berhasil Di Upload";

        //kita pastikan file lama di hapuskan (unlink)

        //cek dulu file lama di db apakah ada di folder target
        if ($gambar_foto && file_exists($cek_file_lama)) {
            if (unlink($cek_file_lama)) {
                //true ==> berhasil hapus file lama
                echo "file lama berhasil di hapus";
            } else {
                echo "gagal menghapus file lama";
            }
        }
    } else {
        //jika tidak ada file baru, gunakan gambar lama
        $gambar_foto = $foto_lama;
        echo "menggunakan foto lama: " . $foto_lama;
    }


    //update edit data ke tbl_petugas
    $sql_user_petugas = "UPDATE tbl_petugas SET 
        nama_petugas         = '$nama_petugas',
        telepon_petugas      = '$telepon',
        path_photo_petugas   = '$gambar_foto',
        jenkel               = '$jenkel',
        branch_id            = '$cabang',
        update_at            = '$tgl' WHERE id_user = '$id_petugas' ";

    if (mysqli_query($KONEKSI, $sql_user_petugas)) {
        echo "<script>
        alert('data berhasil di update')
        </script>";
    } else {
        echo "<script>
        alert('gagal update data')
        </script>";
    }

    return mysqli_affected_rows($KONEKSI);
}

//fungsi hapus petugas
function hapus_petugas()
{
    global $KONEKSI;
    $id_user = $_GET['id'];

    // hapus file gambar yang usernya kita hapus
    $sql = "SELECT * FROM tbl_petugas WHERE id_user='$id_user' " or die("Data tidak ditemukan" . mysqli_error($KONEKSI));
    $hasil = mysqli_query($KONEKSI, $sql);
    $row = mysqli_fetch_assoc($hasil);

    $photo = $row['path_photo_petugas'];
    $target = '../images/petugas/';

    if (!$photo == "") {
        // Jika ada maka kita hapus
        unlink($target . $photo);
    }


    // hapus data di tabel admin
    $query_admin = "DELETE FROM tbl_petugas WHERE id_user='$id_user' ";
    mysqli_query($KONEKSI, $query_admin) or die("Gagal melakukan hapus data admin" . mysqli_error($KONEKSI));

    // hapus data di tabel users
    $query_user = "DELETE FROM tbl_users WHERE id_user='$id_user' ";
    mysqli_query($KONEKSI, $query_user) or die("Gagal melakukan hapus data user" . mysqli_error($KONEKSI));


    return mysqli_affected_rows($KONEKSI);
}

//function tambah jabatan
function tambah_jabatan()
{
    global $KONEKSI;
    global $tgl;

    $id_jabatan     = stripcslashes($_POST['kode']);
    $nama_jabatan   = stripcslashes($_POST['nama_jabatan']);

    //tambahkan data user baru ke tbl_users
    $sql_jabatan = "INSERT INTO tbl_jabatan SET 
    kode_jabatan = '$id_jabatan',
    nama_jabatan = '$nama_jabatan',
    create_at = '$tgl' ";

    mysqli_query($KONEKSI, $sql_jabatan) or die("gagal menambahkan jabatan baru" . mysqli_error($KONEKSI));

    return mysqli_affected_rows($KONEKSI);
}

//function edit jabatan
function edit_jabatan()
{
    global $KONEKSI;
    global $tgl;

    $id_jabatan = stripslashes($_POST['id_jabatan']);
    $nama_jabatan = stripslashes($_POST['nama_jabatan']);

    //update edit data ke tbl admin
    $sql_jabatan = "UPDATE tbl_jabatan SET 
        kode_jabatan = '$id_jabatan',
        nama_jabatan = '$nama_jabatan',
        update_at = '$tgl' WHERE tbl_jabatan.kode_jabatan = '$id_jabatan' ";

    if (mysqli_query($KONEKSI, $sql_jabatan)) {
        echo "<script>
        alert('data berhasil di update')
        </script>";
    } else {
        echo "<script>
        alert('gagal update data')
        </script>";
    }

    return mysqli_affected_rows($KONEKSI);
}

//fungsi hapus jabatan
function hapus_jabatan()
{
    global $KONEKSI;
    global $tgl;

    $id_user = $_GET['id'];

    // hapus file gambar yang usernya kita hapus
    $sql = "SELECT tbl_jabatan.* FROM tbl_jabatan WHERE tbl_jabatan.kode_jabatan='$id_user' " or die("Data tidak ditemukan" . mysqli_error($KONEKSI));
    $hasil = mysqli_query($KONEKSI, $sql);
    $row = mysqli_fetch_assoc($hasil);


    // hapus data di tabel admin
    $query_admin = "DELETE FROM tbl_jabatan WHERE kode_jabatan='$id_user' ";
    mysqli_query($KONEKSI, $query_admin) or die("Gagal melakukan hapus data admin" . mysqli_error($KONEKSI));

    return mysqli_affected_rows($KONEKSI);
}

//fungsi tambah karyawan
function tambah_karyawan($data, $file, $target)
{
    global $KONEKSI;
    global $tgl;

    $id             = htmlspecialchars($data['kode']);
    $nama_karyawan    = htmlspecialchars($data['nama_karyawan']);
    $alamat_ktp       = htmlspecialchars($data['alamat_ktp']);
    $alamat_domisili  = htmlspecialchars($data['alamat_domisili']);
    $email            = htmlspecialchars($data['email']);
    $role             = htmlspecialchars($data['role']);
    $telepon_karyawan = htmlspecialchars($data['telepon']);
    $no_ktp           = htmlspecialchars($data['no_ktp']);
    $date_start       = htmlspecialchars($data['date_start']);
    $date_finish      = htmlspecialchars($data['date_finish']);
    $jenkel           = htmlspecialchars($data['jenkel']);
    $status           = htmlspecialchars($data['status_karyawan']);
    $jabatan          = htmlspecialchars($data['jabatan']);
    $cabang           = htmlspecialchars($data['cabang']);
    $password         = mysqli_escape_string($KONEKSI, $data['password']);
    $password2        = mysqli_escape_string($KONEKSI, $data['password2']);

    //var_dump($cabang);
    //die;
    //var_dump($_POST);
    //var_dump($_FILES);

    //die;
    //pastikan gambar terupload
    $gambar_foto = upload_file_new($data, $file, $target);

    //var_dump($gambar_foto);
    //die;

    //jika gambar tidak di upload operasi di hentikan
    if (!$gambar_foto) {
        return false;
    }

    //cek email yg di daftar apakah sudah dipakai atau belum 
    $result = mysqli_query($KONEKSI, "SELECT email FROM tbl_users WHERE email = '$email' ");

    if (mysqli_fetch_assoc($result)) {
        echo "<script>
        alert('email sudah ada di database :3');
        document.location.href='?pages=karyawan';
        </script>";

        return false;
    }

    //cek konfirmasi password
    if ($password !== $password2) {
        echo "<script>
        alert('konfirmasi email yg di input tidak sama !!!');
        document.location.href='?pages=karyawan';
        </script>";
        return false;
    }

    //kita lakukan enkipsi password yang dia input
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    //tambahkan data user baru ke tbl_users
    $sql_karyawan = "INSERT INTO tbl_users SET 
    id_user = '$id',
    email = '$email',
    password = '$password_hash',
    role = '$role',
    create_at = '$tgl' ";

    var_dump($sql_karyawan);
    // die;
    mysqli_query($KONEKSI, $sql_karyawan) or die("gagal menambahkan user baru") .  mysqli_error($KONEKSI);

    //tambah data user baru ke tbl admin
    $sql_user_karyawan  = "INSERT INTO tbl_karyawan SET 
    nama_karyawan       = '$nama_karyawan',
    telepon_karyawan    = '$telepon_karyawan',
    path_photo_karyawan = '$gambar_foto',
    id_user            = '$id',
    alamat_ktp_karyawan = '$alamat_ktp',
    alamat_domisili_karyawan = '$alamat_domisili',  
    no_ktp             = '$no_ktp',
    date_start         = '$date_start',
    date_finish        = '$date_finish',
    kode_jabatan       = '$jabatan',
    status_karyawan    = '$status',
    jenkel             = '$jenkel',
    branch_id          = '$cabang',
    create_at          = '$tgl' ";

    // echo $sql_user_karyawan;
    var_dump($sql_user_karyawan);

    mysqli_query($KONEKSI, $sql_user_karyawan) or die("gagal menambahkan admin baru" . mysqli_error($KONEKSI));
    // die;
    return mysqli_affected_rows($KONEKSI);
}

//fungsi edit karyawan
function edit_karyawan($data, $file, $target)
{
    global $KONEKSI;
    global $tgl;

    $id_karyawan = stripslashes($data['kode']);
    $nama_karyawan = stripslashes($data['nama_karyawan']);
    $telepon_karyawan = stripslashes($data['telepon']);
    $alamat_domisili = stripslashes($data['alamat_domisili']);
    $alamat_ktp = stripslashes($data['alamat_ktp']);
    $no_ktp = stripslashes($data['no_ktp']);
    $jenkel = stripslashes($data['jenkel']);
    $date_start = stripslashes($data['date_start']);
    $date_finish = stripslashes($data['date_finish']);
    $status_karyawan = stripslashes($data['status_karyawan']);
    $foto_lama = stripslashes($data['photo_db']);
    $cabang = stripslashes($data['cabang']);
    $email = stripslashes($data['email']);


    $cek_file_lama = $target . $foto_lama;

    //cek apakah ada file baru yg di upload oleh server
    if (isset($_FILES['Photo']) && $_FILES['Photo']['error'] !== UPLOAD_ERR_NO_FILE) {

        //kita harus upload file
        $gambar_foto = upload_file_new($data, $file, $target);
        echo $gambar_foto;

        //kita pastikan nama file baru ter upload (debugging)
        echo "File Baru :" . $gambar_foto . "Berhasil Di Upload";

        //kita pastikan file lama di hapuskan (unlink)

        //cek dulu file lama di db apakah ada di folder target
        if ($gambar_foto && file_exists($cek_file_lama)) {
            if (unlink($cek_file_lama)) {
                //true ==> berhasil hapus file lama
                echo "file lama berhasil di hapus";
            } else {
                echo "gagal menghapus file lama";
            }
        }
    } else {
        //jika tidak ada file baru, gunakan gambar lama
        $gambar_foto = $foto_lama;
        echo "menggunakan foto lama: " . $foto_lama;
    }


    //update edit data ke tbl_karyawan
    $sql_user_karyawan = "UPDATE tbl_karyawan SET 
        nama_karyawan       = '$nama_karyawan',
        telepon_karyawan    = '$telepon_karyawan',
        path_photo_karyawan = '$gambar_foto',
        alamat_ktp_karyawan = '$alamat_ktp',
        alamat_domisili_karyawan = '$alamat_domisili',  
        no_ktp             = '$no_ktp',
        date_start         = '$date_start',
        date_finish        = '$date_finish',
       
        status_karyawan    = '$status_karyawan',
        jenkel             = '$jenkel',
        branch_id          = '$cabang',
        update_at            = '$tgl' WHERE id_user = '$id_karyawan' ";

    if (mysqli_query($KONEKSI, $sql_user_karyawan)) {
        echo "<script>
        alert('data berhasil di update')
        </script>";
    } else {
        echo "<script>
        alert('gagal update data')
        </script>";
    }

    return mysqli_affected_rows($KONEKSI);
}

//fungsi hapus karyawan
function hapus_karyawan()
{
    global $KONEKSI;
    $id_user = $_GET['id'];

    // hapus file gambar yang usernya kita hapus
    $sql = "SELECT * FROM tbl_karyawan WHERE id_user='$id_user' " or die("Data tidak ditemukan" . mysqli_error($KONEKSI));
    $hasil = mysqli_query($KONEKSI, $sql);
    $row = mysqli_fetch_assoc($hasil);

    $photo = $row['path_photo_karyawan'];
    $target = '../images/karyawan/';

    if (!$photo == "") {
        // Jika ada maka kita hapus
        unlink($target . $photo);
    }


    // hapus data di tabel admin
    $query_admin = "DELETE FROM tbl_karyawan WHERE id_user='$id_user' ";
    mysqli_query($KONEKSI, $query_admin) or die("Gagal melakukan hapus data admin" . mysqli_error($KONEKSI));

    // hapus data di tabel users
    $query_user = "DELETE FROM tbl_users WHERE id_user='$id_user' ";
    mysqli_query($KONEKSI, $query_user) or die("Gagal melakukan hapus data user" . mysqli_error($KONEKSI));


    return mysqli_affected_rows($KONEKSI);
}

//fungsi tambah pemilik
function tambah_pemilik($data, $file, $target)
{
    global $KONEKSI;
    global $tgl;

    $kode            = htmlspecialchars($data['kode']);
    $nama_pemilik    = htmlspecialchars($data['nama_pemilik']);
    $alamat_ktp      = htmlspecialchars($data['alamat_ktp']);
    $alamat_domisili = htmlspecialchars($data['alamat_domisili']);
    $email           = htmlspecialchars($data['email']);
    $role            = htmlspecialchars($data['role']);
    $telepon_pemilik = htmlspecialchars($data['telepon']);
    $no_ktp          = htmlspecialchars($data['no_ktp']);
    $jenkel          = htmlspecialchars($data['jenkel']);
    $cabang          = htmlspecialchars($data['cabang']);
    $password        = mysqli_escape_string($KONEKSI, $data['password']);
    $password2       = mysqli_escape_string($KONEKSI, $data['password2']);

    //var_dump($cabang);
    //die;
    //var_dump($_POST);
    //var_dump($_FILES);

    //die;
    //pastikan gambar terupload
    $gambar_foto = upload_file_new($data, $file, $target);

    //var_dump($gambar_foto);
    //die;

    //jika gambar tidak di upload operasi di hentikan
    if (!$gambar_foto) {
        return false;
    }

    //cek email yg di daftar apakah sudah dipakai atau belum 
    $result = mysqli_query($KONEKSI, "SELECT email FROM tbl_users WHERE email = '$email' ");

    if (mysqli_fetch_assoc($result)) {
        echo "<script>
        alert('email sudah ada di database :3');
        document.location.href='?pages=user_pemilik';
        </script>";

        return false;
    }

    //cek konfirmasi password
    if ($password !== $password2) {
        echo "<script>
        alert('konfirmasi email yg di input tidak sama !!!');
        document.location.href='?pages=user_pemilik';
        </script>";
        return false;
    }

    //kita lakukan enkipsi password yang dia input
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    //tambahkan data user baru ke tbl_users
    $sql_pemilik = "INSERT INTO tbl_users SET 
    id_user = '$kode',
    email = '$email',
    password = '$password_hash',
    role = '$role',
    create_at = '$tgl' ";

    mysqli_query($KONEKSI, $sql_pemilik) or die("gagal menambahkan user baru") .  mysqli_error($KONEKSI);

    //tambah data user baru ke tbl admin
    $sql_user_pemilik  = "INSERT INTO tbl_pemilik SET 
    nama_pemilik       = '$nama_pemilik',
    telepon_pemilik    = '$telepon_pemilik',
    path_photo_pemilik = '$gambar_foto',
    id_user            = '$kode',
    alamat_ktp_pemilik = '$alamat_ktp',
    alamat_domisili_pemilik = '$alamat_domisili',  
    no_ktp             = '$no_ktp',
    jenkel             = '$jenkel',
    branch_id          = '$cabang',
    create_at          = '$tgl' ";

    mysqli_query($KONEKSI, $sql_user_pemilik) or die("gagal menambahkan admin baru" . mysqli_error($KONEKSI));

    return mysqli_affected_rows($KONEKSI);
}

//fungsi edit pemilik
function edit_pemilik($data, $file, $target)
{
    global $KONEKSI;
    global $tgl;

    $id_pemilik = stripslashes($data['kode']);
    $nama_pemilik = stripslashes($data['nama_pemilik']);
    $telepon_pemilik = stripslashes($data['telepon']);
    $alamat_domisili = stripslashes($data['alamat_domisili']);
    $alamat_ktp = stripslashes($data['alamat_ktp']);
    $no_ktp = stripslashes($data['no_ktp']);
    $jenkel = stripslashes($data['jenkel']);
    $foto_lama = stripslashes($data['photo_db']);
    $cabang = stripslashes($data['cabang']);
    $email = stripslashes($data['email']);

    $cek_file_lama = $target . $foto_lama;

    //cek apakah ada file baru yg di upload oleh server
    if (isset($_FILES['Photo']) && $_FILES['Photo']['error'] !== UPLOAD_ERR_NO_FILE) {

        //kita harus upload file
        $gambar_foto = upload_file_new($data, $file, $target);
        echo $gambar_foto;

        //kita pastikan nama file baru ter upload (debugging)
        echo "File Baru :" . $gambar_foto . "Berhasil Di Upload";

        //kita pastikan file lama di hapuskan (unlink)

        //cek dulu file lama di db apakah ada di folder target
        if ($gambar_foto && file_exists($cek_file_lama)) {
            if (unlink($cek_file_lama)) {
                //true ==> berhasil hapus file lama
                echo "file lama berhasil di hapus";
            } else {
                echo "gagal menghapus file lama";
            }
        }
    } else {
        //jika tidak ada file baru, gunakan gambar lama
        $gambar_foto = $foto_lama;
        echo "menggunakan foto lama: " . $foto_lama;
    }


    //update edit data ke tbl_petugas
    $sql_user_pemilik = "UPDATE tbl_pemilik SET 
        nama_pemilik       = '$nama_pemilik',
        telepon_pemilik    = '$telepon_pemilik',
        path_photo_pemilik = '$gambar_foto',
        alamat_ktp_pemilik = '$alamat_ktp',
        alamat_domisili_pemilik = '$alamat_domisili',  
        no_ktp             = '$no_ktp',
        jenkel             = '$jenkel',
        branch_id          = '$cabang',
        update_at            = '$tgl' WHERE id_user = '$id_pemilik' ";

    //echo $sql_user_pemilik;
    //die;

    if (mysqli_query($KONEKSI, $sql_user_pemilik)) {
        echo "<script>
        alert('data berhasil di update')
        </script>";
    } else {
        echo "<script>
        alert('gagal update data')
        </script>";
    }

    return mysqli_affected_rows($KONEKSI);
}

//fungsi hapus pemilik
function hapus_pemilik()
{
    global $KONEKSI;
    $id_user = $_GET['id'];

    // hapus file gambar yang usernya kita hapus
    $sql = "SELECT * FROM tbl_pemilik WHERE id_user='$id_user' " or die("Data tidak ditemukan" . mysqli_error($KONEKSI));
    $hasil = mysqli_query($KONEKSI, $sql);
    $row = mysqli_fetch_assoc($hasil);

    $photo = $row['path_photo_pemilik'];
    $target = '../images/pemilik/';

    if (!$photo == "") {
        // Jika ada maka kita hapus
        unlink($target . $photo);
    }


    // hapus data di tabel admin
    $query_admin = "DELETE FROM tbl_pemilik WHERE id_user='$id_user' ";
    mysqli_query($KONEKSI, $query_admin) or die("Gagal melakukan hapus data admin" . mysqli_error($KONEKSI));

    // hapus data di tabel users
    $query_user = "DELETE FROM tbl_users WHERE id_user='$id_user' ";
    mysqli_query($KONEKSI, $query_user) or die("Gagal melakukan hapus data user" . mysqli_error($KONEKSI));


    return mysqli_affected_rows($KONEKSI);
}

//fungsi tambah bulan
function tambah_bulan($data)
{
    global $KONEKSI;
    global $tgl;

    $no_bulan   = stripslashes($_POST['no']);
    $nama_bulan = stripslashes($_POST['nama_bulan']);


    //cek email yang didaftar apakah sudah dipakai apa belum
    $result = mysqli_query($KONEKSI, "SELECT nama_bulan FROM tbl_bulan WHERE nama_bulan='$nama_bulan'");

    if (mysqli_fetch_assoc($result)) {
        echo "<script>
        alert('email yang di-input sudah ada di database');
        document.location.href = '?pages=bulan';
    </script>";
        return false;
    }

    //tambahkan data user baru ke tbl_bulan
    $sql_user_bulan = "INSERT INTO tbl_bulan SET
    no_bulan = '$no_bulan',
    nama_bulan = '$nama_bulan',
    create_at = '$tgl' ";

    mysqli_query(
        $KONEKSI,
        $sql_user_bulan
    ) or die("gagal menambahkan user") . mysqli_error($KONEKIS);

    return mysqli_affected_rows($KONEKSI);
}

//edit bulan
function edit_bulan()
{
    global $KONEKSI;
    global $tgl;

    $id_bulan = stripslashes($_POST['id']);
    $no_bulan = stripslashes($_POST['no']);
    $nama_bulan = stripslashes($_POST['nama_bulan']);

    //update data ke tbl_bulan
    $sql = "UPDATE tbl_bulan SET
    no_bulan = '$no_bulan',
    nama_bulan = '$nama_bulan',   
    update_at = '$tgl' WHERE tbl_bulan.id_bulan = '$id_bulan' ";

    // cek apakah query update data berhasil
    if (mysqli_query($KONEKSI, $sql)) {
        echo "
    <script>
        alert('data dah berhasil di-update!');
    </script>";
    } else {
        echo "
    <script>
        alert('data gagal di-update!');
    </script>" . mysqli_affected_rows($KONEKSI);
    }

    return mysqli_affected_rows($KONEKSI);
}

//fungsi hapus bulan
function hapus_bulan()
{
    global $KONEKSI;
    $no_bulan = $_GET['id'];

    //hapus file gambar yang usernya kita hapus
    $sql = "SELECT * FROM tbl_bulan WHERE id_bulan='$no_bulan' " or die("data tidak ditemukan" . mysqli_error($KONEKSI));
    $hasil = mysqli_query($KONEKSI, $sql);
    $row = mysqli_fetch_assoc($hasil);

    // hapus data di tbl_bulan
    $query_bulan = "DELETE FROM tbl_bulan WHERE id_bulan='$no_bulan'";
    mysqli_query($KONEKSI, $query_bulan) or die("gagal ngapus data user admin T-T" . mysqli_error($KONEKSI));

    return mysqli_affected_rows($KONEKSI);
}

//fungsi tambah tahun
function tambah_tahun($data)
{
    global $KONEKSI;
    global $tgl;

    $nama_tahun = stripslashes($_POST['nama']);


    //cek email yang didaftar apakah sudah dipakai apa belum
    $result = mysqli_query($KONEKSI, "SELECT nama_tahun FROM tbl_tahun WHERE nama_tahun='$nama_tahun'");

    if (mysqli_fetch_assoc($result)) {
        echo "<script>
        alert('email yang di-input sudah ada di database');
        document.location.href = '?pages=tahun';
    </script>";
        return false;
    }

    //tambahkan data user baru ke tbl_tahun
    $sql_user_tahun = "INSERT INTO tbl_tahun SET
    nama_tahun = '$nama_tahun',
    create_at = '$tgl' ";

    mysqli_query(
        $KONEKSI,
        $sql_user_tahun
    ) or die("gagal menambahkan tahun") . mysqli_error($KONEKIS);

    return mysqli_affected_rows($KONEKSI);
}

//edit tahun
function edit_tahun()
{
    global $KONEKSI;
    global $tgl;

    $id_tahun = stripslashes($_POST['id_tahun']);
    $nama_tahun = stripslashes($_POST['nama_tahun']);

    //update data ke tbl_tahun
    $sql = "UPDATE tbl_tahun SET
    id_tahun = '$id_tahun',
    nama_tahun = '$nama_tahun',   
    update_at = '$tgl' WHERE tbl_tahun.id_tahun = '$id_tahun' ";

    // cek apakah query update data berhasil
    if (mysqli_query($KONEKSI, $sql)) {
        echo "data dah berhasil di-update!";
    } else {
        echo "data gagal di-update!" . mysqli_affected_rows($KONEKSI);
    }

    return mysqli_affected_rows($KONEKSI);
}

//hapus tahun
function hapus_tahun()
{
    global $KONEKSI;
    $id_tahun = $_GET['id'];

    //hapus file gambar yang usernya kita hapus
    $sql = "SELECT * FROM tbl_tahun WHERE id_tahun='$id_tahun' " or die("data tidak ditemukan" . mysqli_error($KONEKSI));
    $hasil = mysqli_query($KONEKSI, $sql);
    $row = mysqli_fetch_assoc($hasil);

    // hapus data di tbl_tahun
    $query_tahun = "DELETE FROM tbl_tahun WHERE id_tahun='$id_tahun'";
    mysqli_query($KONEKSI, $query_tahun) or die("gagal ngapus data tahun T-T" . mysqli_error($KONEKSI));

    return mysqli_affected_rows($KONEKSI);
}

//fungsi tambah currency
function tambah_currency($data)
{
    global $KONEKSI;
    global $tgl;

    $symbol_currency   = stripslashes($_POST['symbol_mata']);
    $nama_currency = stripslashes($_POST['nama_mata']);

    //cek email yang didaftar apakah sudah dipakai apa belum
    $result = mysqli_query($KONEKSI, "SELECT nama_currency FROM tbl_currency WHERE nama_currency='$nama_currency'");

    if (mysqli_fetch_assoc($result)) {
        echo "<script>
        alert('email yang di-input sudah ada di database');
        document.location.href = '?pages=currency';
    </script>";
        return false;
    }

    //tambahkan data user baru ke tbl_currency
    $sql_user_currency = "INSERT INTO tbl_currency SET
    symbol_currency = '$symbol_currency',
    nama_currency = '$nama_currency',
    create_at = '$tgl' ";

    mysqli_query(
        $KONEKSI,
        $sql_user_currency
    ) or die("gagal menambahkan currency") . mysqli_error($KONEKIS);

    return mysqli_affected_rows($KONEKSI);
}

//edit currency
function edit_currency()
{
    global $KONEKSI;
    global $tgl;

    $kode_currency = stripslashes($_POST['symbol_mata']);
    $nama_currency = stripslashes($_POST['nama_mata']);
    $id = stripslashes($_POST['id']);

    //update data ke tbl_currency
    $sql = "UPDATE tbl_currency SET
    symbol_currency = '$kode_currency',
    nama_currency = '$nama_currency',   
    update_at = '$tgl' WHERE id_currency = '$id' ";

    // cek apakah query update data berhasil
    if (mysqli_query($KONEKSI, $sql)) {
        echo "data dah berhasil di-update!";
    } else {
        echo "data gagal di-update!" . mysqli_affected_rows($KONEKSI);
    }

    return mysqli_affected_rows($KONEKSI);
}

//fungsi hapus currency
function hapus_currency()
{
    global $KONEKSI;
    $id_currency = $_GET['id'];

    //hapus file gambar yang usernya kita hapus
    $sql = "SELECT * FROM tbl_currency WHERE id_currency='$id_currency' " or die("data tidak ditemukan" . mysqli_error($KONEKSI));
    $hasil = mysqli_query($KONEKSI, $sql);
    $row = mysqli_fetch_assoc($hasil);

    // hapus data di tbl_currency
    $query_currency = "DELETE FROM tbl_currency WHERE id_currency='$id_currency'";
    mysqli_query($KONEKSI, $query_currency) or die("gagal ngapus data currency T-T" . mysqli_error($KONEKSI));

    return mysqli_affected_rows($KONEKSI);
}

//fungsi tambah facility
function tambah_facility($data)
{
    global $KONEKSI;
    global $tgl;

    $kode_facility   = stripslashes($_POST['kode']);
    $nama_facility = stripslashes($_POST['nama']);

    //cek email yang didaftar apakah sudah dipakai apa belum
    $result = mysqli_query($KONEKSI, "SELECT kode_facility FROM tbl_facility WHERE kode_facility='$kode_facility'");

    if (mysqli_fetch_assoc($result)) {
        echo "<script>
        alert('data yang di-input sudah ada di database');
        document.location.href = '?pages=facility';
    </script>";
        return false;
    }

    //tambahkan data user baru ke tbl_facility
    $sql_user_facility = "INSERT INTO tbl_facility SET
    kode_facility = '$kode_facility',
    nama_facility = '$nama_facility',
    create_at = '$tgl' ";

    mysqli_query(
        $KONEKSI,
        $sql_user_facility
    ) or die("gagal menambahkan facility") . mysqli_error($KONEKIS);

    return mysqli_affected_rows($KONEKSI);
}

//edit facility
function edit_facility()
{
    global $KONEKSI;
    global $tgl;

    $kode_facility = stripslashes($_POST['kode']);
    $nama_facility = stripslashes($_POST['nama']);

    //update data ke tbl_facility
    $sql = "UPDATE tbl_facility SET
    kode_facility = '$kode_facility',
    nama_facility = '$nama_facility',
    update_at = '$tgl' WHERE tbl_facility.kode_facility = '$kode_facility' ";

    // cek apakah query update data berhasil
    if (mysqli_query($KONEKSI, $sql)) {
        echo "data dah berhasil di-update!";
    } else {
        echo "data gagal di-update!" . mysqli_affected_rows($KONEKSI);
    }

    return mysqli_affected_rows($KONEKSI);
}

//fungsi hapus facility
function hapus_facility()
{
    global $KONEKSI;
    $id_facility = $_GET['id'];

    //hapus file gambar yang usernya kita hapus
    $sql = "SELECT * FROM tbl_facility WHERE kode_facility='$id_facility' " or die("data tidak ditemukan" . mysqli_error($KONEKSI));
    $hasil = mysqli_query($KONEKSI, $sql);
    $row = mysqli_fetch_assoc($hasil);

    // hapus data di tbl_facility
    $query_facility = "DELETE FROM tbl_facility WHERE kode_facility='$id_facility'";
    mysqli_query($KONEKSI, $query_facility) or die("gagal ngapus data facility T-T" . mysqli_error($KONEKSI));

    return mysqli_affected_rows($KONEKSI);
}

//fungsi tambah floor
function tambah_floor($data)
{
    global $KONEKSI;
    global $tgl;

    $no_lantai   = stripslashes($_POST['no_lantai']);
    $cabang = stripslashes($_POST['cabang']);

    //cek email yang didaftar apakah sudah dipakai apa belum
    $result = mysqli_query($KONEKSI, "SELECT floor_no FROM tbl_floor WHERE floor_no='$no_lantai'");

    if (mysqli_fetch_assoc($result)) {
        echo "<script>
        alert('email yang di-input sudah ada di database');
        document.location.href = '?pages=floor';
    </script>";
        return false;
    }

    //tambahkan data user baru ke tbl_floor
    $sql_user_floor = "INSERT INTO tbl_floor SET
    floor_no = '$no_lantai',
    branch_id = '$cabang',
    create_at = '$tgl' ";

    mysqli_query(
        $KONEKSI,
        $sql_user_floor
    ) or die("gagal menambahkan floor") . mysqli_error($KONEKIS);

    return mysqli_affected_rows($KONEKSI);
}

//edit floor
function edit_floor()
{
    global $KONEKSI;
    global $tgl;

    $floor_no = stripslashes($_POST['no_lantai']);
    $id_cabang = stripslashes($_POST['cabang']);
    $id_floor = stripslashes($_POST['id_floor']);

    //update data ke tbl_floor
    $sql = "UPDATE tbl_floor SET
    id_floor = '$id_floor',
    floor_no = '$floor_no',
    branch_id = '$id_cabang',
    update_at = '$tgl' WHERE tbl_floor.id_floor = '$id_floor' ";

    // cek apakah query update data berhasil
    if (mysqli_query($KONEKSI, $sql)) {
        echo "data dah berhasil di-update!";
    } else {
        echo "data gagal di-update!" . mysqli_affected_rows($KONEKSI);
    }

    return mysqli_affected_rows($KONEKSI);
}

//fungsi hapus floor
function hapus_floor()
{
    global $KONEKSI;
    $id_floor = $_GET['id'];

    //hapus file gambar yang usernya kita hapus
    $sql = "SELECT * FROM tbl_floor WHERE id_floor='$id_floor' " or die("data tidak ditemukan" . mysqli_error($KONEKSI));
    $hasil = mysqli_query($KONEKSI, $sql);
    $row = mysqli_fetch_assoc($hasil);

    // hapus data di tbl_floor
    $query_floor = "DELETE FROM tbl_floor WHERE id_floor='$id_floor'";
    mysqli_query($KONEKSI, $query_floor) or die("gagal ngapus data floor T-T" . mysqli_error($KONEKSI));

    return mysqli_affected_rows($KONEKSI);
}