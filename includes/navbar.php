<?php if (isset($_SESSION['user_id'])) { ?>
<nav class="navbar navbar-expand-lg bg-body-tertiary shadow fixed-top">
    <div class="container">
    <a class="navbar-brand" href="dashboard.php">
      <img src="/assets/images/logo_header.png" alt="Bootstrap" width="251">
    </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">

            <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'driver'|| $_SESSION['role'] == 'secretary') { ?>               
            <li class="nav-item"><a class="nav-link" href="service.php"><i class="bi bi-bus-front"> </i> Servis</a></li>
            <?php } ?>     
            <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'secretary') { ?>               
            <li class="nav-item"><a class="nav-link" href="teachers.php"><i class="bi bi-calendar3"> </i> Ders Programı</a></li>
            <?php } ?>                                
                          
            <?php if ($_SESSION['role'] == 'teacher') { ?>               
            <li class="nav-item"><a class="nav-link" href="weeklytable_detail.php?teacher_id=<?php echo $_SESSION['user_id']; ?>"><i class="bi bi-calendar-check"> </i> Ders Programım</a></li>
            <?php } ?>
<?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'secretary') { ?>  
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="dashboard.php" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-backpack2-fill"> </i> Öğrenci Listele</a>
                    <ul class="dropdown-menu">
                                     
                        <li><a class="dropdown-item" href="student_add.php">Öğrenci Ekle</a></li>                              
                        <li><a class="dropdown-item" href="dashboard.php">Öğrencileri Listele</a></li>
                        
                    </ul>
            </li>
            <?php } ?>  
            <?php if ($_SESSION['role'] == 'admin') { ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-person-fill-gear"> </i> Kullanıcı Ayarları</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="user_add.php">Kullanıcı Ekle</a></li>
                            <li><a class="dropdown-item" href="user_list.php">Kullanıcı Listesi</a></li>
                            <li><a class="dropdown-item" href="upload_csv.php">Veri Yükle</a></li>
                        </ul>
                </li>
            <?php } ?>
            <li class="nav-item"><a class="nav-link" href="logout.php"><i class="bi bi-x-square-fill"> </i>Çıkış Yap</a></li>
            </ul>
        </div>
    </div>
</nav>

<!-- İkinci Navbar (Arama ve Filtreleme) -->

<div class="bos" style=" height: 75px"></div>
<?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'secretary'|| $_SESSION['role'] == 'reporter') { ?>    
<div class="row m-0">
    <nav class="navbar navbar-expand-lg bg-body-tertiary">
        <div class="container">
            <button class="navbar-toggler mx-auto " type="button" data-bs-toggle="collapse" data-bs-target="#navbarTogglerSearch" aria-controls="navbarTogglerSearch" aria-expanded="false" aria-label="Toggle navigation">
                <span class=""><i class="bi bi-search"> Ara</i></span>
            </button>
            <div class="collapse justify-content-center navbar-collapse" id="navbarTogglerSearch">
                <form class="d-flex" role="search" action="student_search.php" method="get" autocomplete="off">
                    <div class="row">
                        <div class="col-6 col-lg-2 mt-3">
                            <div class="form-floating">
                                <input type="text" name="first_name" class="form-control me-2" id="floatingInputFirstName" placeholder="Öğrenci Adı">
                                <label for="floatingInputFirstName">Öğrenci Adı</label>
                            </div>
                        </div>
                        <div class="col-6 col-lg-2 mt-3">
                            <div class="form-floating">
                                <input type="text" name="last_name" class="form-control me-2" id="floatingInputLastName" placeholder="Öğrenci Soyadı">
                                <label for="floatingInputLastName">Öğrenci Soyadı</label>
                            </div>
                        </div>
                        <div class="col-6 col-lg-2 mt-3">
                            <div class="form-floating">
                                <input type="text" name="tc_no" class="form-control me-2" id="floatingInputTcNo" placeholder="T.C. Kimlik No">
                                <label for="floatingInputTcNo">T.C. Kimlik No</label>
                            </div>
                        </div>
                        <div class="col-6 col-lg-2 mt-3">
                            <div class="form-floating">
                                <select name="distance" class="form-select me-2" id="floatingSelectDistance">
                                    <option value="">Tüm Mesafeler</option>
                                    <option value="near">Civar Bölge</option>
                                    <option value="medium">Yakın Bölge</option>
                                    <option value="far">Uzak Bölge</option>
                                </select>
                                <label for="floatingSelectDistance">Mesafe</label>
                            </div>
                        </div>
                        <div class="col-6 col-lg-2 mt-3">
                            <div class="form-floating">
                                <select name="days[]" class="form-select me-2" id="floatingSelectDays">
                                    <option value="">Tüm Günler</option>
                                    <option value="Pazartesi">Pazartesi</option>
                                    <option value="Salı">Salı</option>
                                    <option value="Çarşamba">Çarşamba</option>
                                    <option value="Perşembe">Perşembe</option>
                                    <option value="Cuma">Cuma</option>
                                    <option value="Cumartesi">Cumartesi</option>
                                </select>
                                <label for="floatingSelectDays">Gün Seç</label>
                            </div>
                        </div>
                        <div class="col-6 col-lg-2 mt-3">
                            <button type="submit" class="btn btn-success btn-lg w-100 h-100" name="filtreAra">Ara</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </nav>
</div>
<?php } ?>
<?php } ?>

<?php if (basename($_SERVER['PHP_SELF']) !== 'login.php'): ?>
<div class="navigation-buttons">
    <a href="javascript:history.back()" class="btn btn-success nav-left-btn">
        <i class="bi bi-arrow-left-circle"></i>
    </a>
    <a href="javascript:history.forward()" class="btn btn-success nav-right-btn">
        <i class="bi bi-arrow-right-circle"></i>
    </a>
</div>

<style>
.navigation-buttons {
    position: fixed;
    top: 75px;
    left: 0;
    right: 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
    z-index: 1000;
    pointer-events: none;
}

.nav-right-btn {
    pointer-events: auto;
    display: inline-block;
    padding: 10px 20px;
    font-size: 18px;
    border-radius: 25px 0px 0px 25px;
}

.nav-left-btn {
    pointer-events: auto;
    display: inline-block;
    padding: 10px 20px;
    font-size: 18px;
    border-radius: 0px 25px 25px 0px;
}

.nav-left-btn {
    margin-left: 0px;
}

.nav-right-btn {
    margin-right: 0px;
}
</style>
<?php endif; ?>