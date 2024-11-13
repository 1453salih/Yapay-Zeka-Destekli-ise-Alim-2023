<?php
include '../../session.php';

include '../../Router/auth.php';
checkUserType('employer'); // erişim tipi

try {
    include '../../db.php';
} catch (PDOException $e) {
    echo 'Veritabanı bağlantısı kurulamadı: ' . $e->getMessage();
    exit();
}

if (!isset($_SESSION['uye_id'])) {
    echo 'Kullanıcı oturumu başlatılmamış.';
    exit();
}

$userId = $_SESSION['uye_id']; 


try {
    // Fetch user data
    $query = $conn->prepare("SELECT * FROM uyeler WHERE uye_id = :user_id");
    $query->execute(['user_id' => $userId]);
    $user = $query->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        $user = []; 
    }

    // Fetch user contact data
    $query = $conn->prepare("SELECT * FROM uye_iletisim WHERE uye_id = :user_id");
    $query->execute(['user_id' => $userId]);
    $userContact = $query->fetch(PDO::FETCH_ASSOC);
    if (!$userContact) {
        $userContact = []; 
    }

    // Fetch user info
    $query = $conn->prepare("SELECT * FROM uye_bilgi WHERE uye_id = :user_id");
    $query->execute(['user_id' => $userId]);
    $userInfo = $query->fetch(PDO::FETCH_ASSOC);
    if (!$userInfo) {
        $userInfo = []; 
    }

    // Fetch user company data
    $query = $conn->prepare("SELECT * FROM sirketler WHERE uye_id = :user_id");
    $query->execute(['user_id' => $userId]);
    $userCompany = $query->fetch(PDO::FETCH_ASSOC);
    if (!$userCompany) {
        $userCompany = []; 
    }

    // Kullanıcının şirket sektörü ID'sini alıp, userCompany boş olabilir bu yüzden kontrol eder
    $selectedSectorId = isset($userCompany['sektor_id']) ? $userCompany['sektor_id'] : null;

    // Fetch sectors data
    $query = $conn->prepare("SELECT * FROM sektorler");
    $query->execute();
    $sectors = $query->fetchAll(PDO::FETCH_ASSOC);
    if (!$sectors) {
        $sectors = []; 
    }
} catch (PDOException $e) {
    echo 'Veritabanı hatası: ' . $e->getMessage();
    exit();
} catch (Exception $e) {
    echo 'Hata: ' . $e->getMessage();
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">

<?php
$page_title = "İlanlar - JobFind";
$page_css = "../css/e_profil-details.css";
include '../partials/header.php';
?>
<link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css" rel="stylesheet">

<body>
    <header>
        <?php include '../partials/navbar.php'; ?>
    </header>
    <?php
    $breadcrumbs = [
        [
            'title' => 'Profilim'
        ]
    ];
    include '../partials/breadcrumb.php'; ?>

    <div class="container mt-5 main_e_profile">
        <div class="card">
            <div class="card-body">
                <form id="profileForm" method="POST" enctype="multipart/form-data" action="e_save_profile.php">
                    <!-- Kişisel Bilgiler -->
                    <div id="personalInfo">
                        <h5 class="card-title">Kişisel Bilgiler</h5>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="firstName">Adınız</label>
                                <input type="text" class="form-control input-field" id="firstName" name="firstName" placeholder="Adınız" value="<?php echo htmlspecialchars($user['uye_adi'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="lastName">Soyadınız</label>
                                <input type="text" class="form-control input-field" id="lastName" name="lastName" placeholder="Soyadınız" value="<?php echo htmlspecialchars($user['uye_soyadi'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="gender">Cinsiyetiniz</label>
                                <select id="gender" name="gender" class="form-control input-field">
                                    <option value="Erkek" <?php if (isset($userInfo['cinsiyet']) && $userInfo['cinsiyet'] == 'Erkek') echo 'selected'; ?>>Erkek</option>
                                    <option value="Kadın" <?php if (isset($userInfo['cinsiyet']) && $userInfo['cinsiyet'] == 'Kadın') echo 'selected'; ?>>Kadın</option>
                                    <option value="Belirtilmemiş" <?php if (isset($userInfo['cinsiyet']) && $userInfo['cinsiyet'] == 'Belirtilmemiş') echo 'selected'; ?>>Belirtilmemiş</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="dob">Doğum Tarihi</label>
                                <input type="date" class="form-control input-field" id="dob" name="dob" value="<?php echo htmlspecialchars($userInfo['dogum_tarihi'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="formFile" class="form-label">Profil Fotoğrafı</label>
                                <input class="form-control uploadPhoto" type="file" id="formFile" name="formFile">
                                <small id="filePath" class="form-text text-muted">
                                    <?php echo htmlspecialchars($userInfo['foto'] ?? 'Dosya seçilmedi', ENT_QUOTES, 'UTF-8'); ?>
                                </small>
                                <div class="preview" id="preview"></div>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="github"><i class="bi bi-github" style="margin-right: 8px; vertical-align:0px;"></i>Github</label>
                                <input id="github" name="github" class="form-control input-field" placeholder="Github Profil Linki" value="<?php echo htmlspecialchars($userInfo['github'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                        </div>
                        <!-- Mevcut fotoğraf yolunu gizli bir input alanında saklar -->
                        <input type="hidden" id="existingPhotoPath" name="existingPhotoPath" value="<?php echo htmlspecialchars($userInfo['foto'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="form-row" style="position: relative;">
                            <div class="form-group col-md-6">
                                <img id="preview" src="<?php echo htmlspecialchars($userInfo['foto'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" alt="Seçilen Fotoğraf" style="display: block; position: absolute; top: 10px; left: 0; max-width: 100%;">
                                <input type="hidden" id="profilePhotoPath" name="profilePhotoPath">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="facebook"><i class="bi bi-facebook" style="margin-right: 8px; vertical-align:0px;"></i>Facebook</label>
                                <input id="facebook" name="facebook" class="form-control input-field" placeholder="Facebook Profil Linki" value="<?php echo htmlspecialchars($userInfo['facebook'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                        </div>

                        <div class="form-row mb-5">
                            <div class="form-group col-md-6">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="linkedin"><i class="bi bi-linkedin" style="margin-right: 8px; vertical-align:0px;"></i>LinkedIn</label>
                                <input id="linkedin" name="linkedin" class="form-control input-field" placeholder="LinkedIn Profil Linki" value="<?php echo htmlspecialchars($userInfo['linkedin'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                        </div>
                        <button type="button" class="btn btn-primary form_button" onclick="showNext('addressInfo')">Kaydet ve Devam Et</button>
                    </div>

                    <!-- Adres Bilgileri -->
                    <div id="addressInfo" style="display: none;">
                        <h5 class="card-title">Adres Bilgileri</h5>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="ulke">Ülke</label>
                                    <select class="form-control input-field" id="ulke" name="ulke">
                                        <option value="Türkiye">Türkiye</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="sehir">Şehir</label>
                                    <select class="form-control input-field" id="il" name="il">
                                        <option value="">İl Seçin</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="ilce">İlçe</label>
                                    <select class="form-control input-field" id="ilce" name="ilce">
                                        <option value="">İlçe Seçin</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="email">E-Posta</label>
                                <input type="email" class="form-control input-field" id="email" name="email" placeholder="E-Posta Adresiniz" value="<?php echo htmlspecialchars($user['eposta'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="telefon">Telefon</label>
                                <input type="text" class="form-control input-field" id="telefon" name="telefon" placeholder="(555) 555-1212" maxlength="14" value="<?php echo htmlspecialchars($user['uye_tel'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 text-start">
                                <button type="button" class="btn btn-warning form_button text-white" onclick="showPrevious('personalInfo')"><i class="bi bi-arrow-left" style="color:#fff;"></i> Önceki Sayfa</button>
                            </div>
                            <div class="col-md-6 text-end">
                                <button type="button" class="btn btn-primary form_button" onclick="showNext('jobPreferences')">Kaydet ve Devam Et</button>
                            </div>
                        </div>
                    </div>

                    <!-- Şirket Bilgileri -->
                    <div id="jobPreferences" style="display: none;">
                        <h5 class="card-title">Şirket Bilgileri</h5>
                        <div class="form-group">
                            <label for="companyName">Şirket İsmi:</label>
                            <input name="companyName" type="text" class="form-control input-field" id="companyName" placeholder="Şirket İsmi" value="<?php echo htmlspecialchars($userCompany['sirket_adi'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="form-group">
                            <label for="jobTitle">Şirket Sektörü:</label>
                            <select id="jobTitle" name="jobTitle" class="form-control input-field">
                                <option>Seçiniz...</option>
                                <?php
                                // Veritabanından çekilen sektörleri döngü ile <option> etiketlerine ekle
                                if (!empty($sectors)) {
                                    foreach ($sectors as $sektor) {
                                        // Seçili sektör kontrolü
                                        $selected = ($sektor['id'] == $selectedSectorId) ? 'selected' : '';
                                        echo '<option value="' . htmlspecialchars($sektor['id']) . '" ' . $selected . '>' . htmlspecialchars($sektor['sektor']) . '</option>';
                                    }
                                } else {
                                    echo '<option value="">Sektör bulunamadı</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="companyPhone">Şirket Telefonu:</label>
                            <input type="tel" id="companyPhone" name="companyPhone" class="form-control input-field" placeholder="(212) 555-1212" maxlength="14" value="<?php echo htmlspecialchars($userCompany['sirket_telefon'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

                        </div>
                        <div class="form-group">
                            <label for="companyAddress">Şirket Adresi:</label>
                            <input type="text" id="companyAddress" name="companyAddress" class="form-control input-field" placeholder="Şirket Adresi" value="<?php echo htmlspecialchars($userCompany['sirket_adresi'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="form-group">
                            <label for="registerNo">Ticaret Sicil No:</label>
                            <input type="text" id="registerNo" name="registerNo" class="form-control input-field" placeholder="Ticaret Sicil No" maxlength="16" value="<?php echo htmlspecialchars($userCompany['sicil_no'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="form-group">
                            <label for="taxNumber">Vergi No:</label>
                            <input type="text" id="taxNumber" name="taxNumber" class="form-control input-field" placeholder="Vergi No" maxlength="10" value="<?php echo htmlspecialchars($userCompany['vergi_no'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="row">
                            <div class="col-md-6 text-start">
                                <button type="button" class="btn btn-warning form_button text-white" onclick="showPrevious('addressInfo')"><i class="bi bi-arrow-left" style="color:#fff;"></i> Önceki Sayfa</button>
                            </div>
                            <div class="col-md-6 text-end">
                                <button type="submit" class="btn btn-success form_button">Kaydet ve Tamamla</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <!-- Modal -->
        <div class="modal fade" id="photoModal" tabindex="-1" role="dialog" aria-labelledby="photoModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="photoModalLabel">Fotoğrafı Düzenle</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="img-container">
                            <img id="image" src="#" alt="Profil Fotoğrafı" style="max-width: 100%;">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Kapat</button>
                        <button type="button" class="btn btn-primary" id="crop">Kırp ve Kaydet</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../../js/vendor/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>
    <script>
        function showNext(sectionId) {
            document.getElementById('personalInfo').style.display = 'none';
            document.getElementById('addressInfo').style.display = 'none';
            document.getElementById('jobPreferences').style.display = 'none';
            document.getElementById(sectionId).style.display = 'block';
        }

        function showPrevious(sectionId) {
            document.getElementById('personalInfo').style.display = 'none';
            document.getElementById('addressInfo').style.display = 'none';
            document.getElementById('jobPreferences').style.display = 'none';
            document.getElementById(sectionId).style.display = 'block';
        }

        // ! TELEFON STYLE
        function maskInput(id) {
            const phoneInput = document.getElementById(id);

            function applyMask(input) {
                const phoneNumber = input.value.replace(/\D/g, '');

                const phoneNumberPattern = /^(\d{3})(\d{3})(\d{4})$/;

                if (phoneNumberPattern.test(phoneNumber)) {
                    input.value = phoneNumber.replace(phoneNumberPattern, '($1) $2-$3');
                }
            }

            phoneInput.addEventListener('input', () => {
                applyMask(phoneInput);
            });
        }

        window.onload = function() {
            maskInput('companyPhone');
            maskInput('telefon');
        }

        // ! İL iLçe Ülke Json
        $(document).ready(function() {
            // İllerin JSON verisini yükler
            $.getJSON('../../turkiye_sehirler/il.json', function(ilData) {
                var iller = ilData[2].data; // İllerin verisini alır
                $.each(iller, function(index, il) {
                    $('#il').append('<option value="' + il.id + '">' + il.name + '</option>');
                });

                // Sayfa yüklendiğinde şehir ve ilçeleri doldurur
                var initialIl = "<?php echo isset($userContact['sehir']) ? $userContact['sehir'] : ''; ?>";
                if (initialIl) {
                    $('#il').val(initialIl).trigger('change');
                }
            });

            // İl seçildiğinde ilçeleri yükler
            $('#il').change(function() {
                var selectedIl = $(this).val();
                $('#ilce').empty().append('<option value="">İlçe Seçin</option>');
                if (selectedIl) {
                    $.getJSON('../../turkiye_sehirler/ilce.json', function(ilceData) {
                        var ilceler = ilceData[2].data; // İlçelerin verisini alır
                        $.each(ilceler, function(index, ilce) {
                            if (ilce.il_id == selectedIl) {
                                $('#ilce').append('<option value="' + ilce.id + '">' + ilce.name + '</option>');
                            }
                        });

                        // İlçe verilerini yükledikten sonra seçili ilçe ayarlar
                        var initialIlce = "<?php echo isset($userContact['ilce']) ? $userContact['ilce'] : ''; ?>";
                        if (initialIlce) {
                            $('#ilce').val(initialIlce);
                        }
                    });
                }
            });
        });
        // ! ---------------Fotoğraf yükleme alanı---------------
        document.getElementById('formFile').addEventListener('change', function(event) {
            var files = event.target.files;
            if (files && files.length > 0) {
                var file = files[0];
                var reader = new FileReader();
                reader.onload = function(e) {
                    var image = document.getElementById('image');
                    image.src = e.target.result;
                    $('#photoModal').modal('show');
                };
                reader.readAsDataURL(file);
            }
        });

        $('#photoModal').on('shown.bs.modal', function() {
            var image = document.getElementById('image');
            cropper = new Cropper(image, {
                aspectRatio: 1,
                viewMode: 1,
                autoCropArea: 1,
                responsive: true,
                zoomable: false
            });
        }).on('hidden.bs.modal', function() {
            cropper.destroy();
            cropper = null;
        });

        document.getElementById('crop').addEventListener('click', function() {
            var canvas = cropper.getCroppedCanvas({
                width: 200,
                height: 200
            });
            canvas.toBlob(function(blob) {
                var formData = new FormData();
                formData.append('croppedImage', blob);

                $.ajax('upload_cropped_image.php', {
                    method: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        var result = JSON.parse(response);
                        if (result.url) {
                            var url = result.url; // Sunucudan dönen dosya URL'si
                            var img = document.getElementById('preview');
                            img.src = url;
                            img.style.display = 'block';
                            $('#photoModal').modal('hide');
                            // hidden inputa dosya yolunu ekler
                            var profilePhotoPath = document.getElementById('profilePhotoPath');
                            if (profilePhotoPath) {
                                profilePhotoPath.value = url;
                            } else {
                                console.error('profilePhotoPath elementi bulunamadı.');
                            }
                        } else {
                            console.error('Yükleme hatası:', result.error);
                        }
                    },
                    error: function() {
                        console.log('Upload error');
                    }
                });
            });
        });
        // !------------Değişiklik yoksa Aynı kalsın---------------
        document.querySelector('form').addEventListener('submit', function(event) {
            var profilePhotoPath = document.getElementById('profilePhotoPath');
            var existingPhotoPath = document.getElementById('existingPhotoPath');
            if (!profilePhotoPath.value && existingPhotoPath.value) {
                profilePhotoPath.value = existingPhotoPath.value;
            }
        });
    </script>

</body>

</html>