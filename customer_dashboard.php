<?php
session_start();
require 'config.php';

// تأكد من تسجيل الدخول كمستخدم عادي
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    header("Location: login.php?user_type=customer");
    exit;
}

$user_id = $_SESSION['user_id'];

// جلب بيانات العميل الحالية لملء الواجهة بالاسم والصورة
try {
    $user_stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $user_stmt->execute([$user_id]);
    $user = $user_stmt->fetch();
} catch (PDOException $e) {
    $user = ['fullname' => 'عميل كرين المميز'];
}

// جلب السائقين ومواقعهم الفعلية من قاعدة البيانات لربطهم بالخريطة
try {
    $drivers_stmt = $pdo->query("
        SELECT dl.driver_id, dl.latitude, dl.longitude, d.fullname, d.phone, d.province, d.wheel_number, d.wheel_type, d.wheel_color, d.wheel_model
        FROM driver_locations dl
        JOIN drivers d ON dl.driver_id = d.id
    ");
    $drivers = $drivers_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $drivers = []; // مصفوفة فارغة لتفادي توقف الصفحة
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>لوحة التحكم الجغرافية - تطبيق كرين العراقي</title>

  <!-- استدعاء مكتبات التصميم والخريطة -->
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
  
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap');
    body { font-family: 'Cairo', sans-serif; }
    
    /* موازنة ارتفاع الخريطة لملء الشاشة مع الهيدر */
    #map { height: calc(100vh - 80px); width: 100%; }
    
    /* تأثير الفلتر المظلم السينمائي للخريطة عند تفعيله */
    .dark-map-style {
        filter: invert(1) hue-rotate(180deg) brightness(0.9) contrast(1.2);
    }
    
    /* إخفاء شريط التمرير الافتراضي في القائمة الجانبية لتصميم أفضل */
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
  </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex flex-col overflow-hidden">

  <header class="h-20 bg-slate-900/90 backdrop-blur-md border-b border-slate-800 flex items-center justify-between px-6 z-30 shadow-2xl relative">
    <div class="flex items-center gap-4">
      <span class="text-3xl animate-pulse">🚜</span>
      <div>
        <h1 class="text-md sm:text-lg font-black text-white leading-none">تطبيق <span class="text-amber-500">كرين العراق</span></h1>
        <p class="text-[10px] text-slate-400 mt-1">أهلاً بك، <span class="text-amber-500 font-bold"><?= htmlspecialchars($user['fullname']) ?></span> 👋</p>
      </div>
    </div>

    <!-- شريط البحث السريع والذكي لفلترة السائقين بالاسم أو المحافظة -->
    <div class="hidden md:flex items-center max-w-md w-full px-6">
      <div class="relative w-full">
        <span class="absolute inset-y-0 right-4 flex items-center text-slate-400 text-sm">🔍</span>
        <input type="text" id="driverSearch" onkeyup="filterDrivers()" 
               placeholder="ابحث عن سائق باسمه، محافظته، أو نوع الونش..." 
               class="w-full pl-4 pr-10 py-2.5 rounded-xl bg-slate-950/80 border border-slate-800 text-xs text-white placeholder-slate-500 focus:border-amber-500 outline-none transition" />
      </div>
    </div>

    <!-- أزرار الإجراءات السريعة وأمان الحساب -->
    <div class="flex items-center gap-2 sm:gap-3">
      <button id="themeToggle" class="p-2.5 bg-slate-950/60 hover:bg-slate-850 rounded-xl border border-slate-800 transition text-sm" title="تبديل مظهر الخريطة">🌙</button>
      <a href="account_settings.php" class="p-2.5 bg-slate-950/60 hover:bg-slate-850 rounded-xl border border-slate-800 transition text-xs font-bold text-slate-300 flex items-center gap-1.5">
         ⚙️ <span class="hidden sm:inline">الإعدادات</span>
      </a>
      <a href="logout.php" class="p-2.5 bg-red-950/20 text-red-400 hover:bg-red-950/40 border border-red-900/30 rounded-xl transition text-xs font-bold flex items-center gap-1.5">
         🚪 <span class="hidden sm:inline">تسجيل خروج</span>
      </a>
    </div>
  </header>

  <div class="flex flex-1 flex-col lg:flex-row relative">
    
    <!-- القائمة الجانبية المتميزة لعرض السائقين ومحرك البحث عن المواقع -->
    <aside class="w-full lg:w-96 bg-slate-900 border-l border-slate-800 flex flex-col z-20 relative max-h-[35vh] lg:max-h-full">
      
      <!-- محرك البحث الجغرافي السريع عن المناطق -->
      <div class="p-4 border-b border-slate-800 space-y-3 bg-slate-950/40">
        <h2 class="text-xs font-black text-slate-400 uppercase tracking-wider">📍 ابحث عن منطقة أو حدد موقعك</h2>
        <div class="flex gap-2">
          <input type="text" id="geoInput" placeholder="مثال: المنصور، بغداد..." 
                 class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white placeholder-slate-500 outline-none focus:border-amber-500 transition" />
          <button onclick="searchLocation()" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-slate-950 text-xs font-bold rounded-xl transition">بحث</button>
        </div>
        
        <div class="grid grid-cols-2 gap-2">
          <button id="gpsBtn" class="py-2.5 px-3 bg-slate-900 hover:bg-slate-800 text-[10px] font-bold text-slate-200 rounded-xl border border-slate-800 transition flex items-center justify-center gap-1">
            📍 موقعي الحالي
          </button>
          <a href="request_service.php" class="py-2.5 px-3 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-[10px] font-black text-slate-950 rounded-xl transition text-center flex items-center justify-center">
            🚚 اطلب كرين فوراً
          </a>
        </div>
        <!-- مؤشر دقة الموقع -->
        <p id="gpsStatus" class="text-xs text-slate-500 font-bold text-center mt-1">⏳ جارٍ تحديد موقعك...</p>
      </div>

      <!-- قائمة السائقين المتصلين تفاعلياً -->
      <div class="p-4 border-b border-slate-800 flex justify-between items-center bg-slate-950/20">
        <span class="text-xs font-bold text-slate-400">السائقون النشطون بالقرب منك:</span>
        <span id="driversCount" class="text-[10px] bg-amber-500/10 text-amber-500 font-bold px-2 py-0.5 rounded-full border border-amber-500/20">0 متاح</span>
      </div>

      <div id="driversList" class="flex-1 overflow-y-auto p-4 space-y-3 no-scrollbar">
         <!-- سيتم حقن بطاقات السائقين المتاحة تفاعلياً هنا عبر الجافاسكربت -->
      </div>
    </aside>

    <!-- واجهة الخريطة التفاعلية الفخمة -->
    <div class="flex-1 relative">
      <div id="map"></div>
      
      <!-- تنبيه عائم في حال لم توجد بيانات -->
      <div id="noDriversNotice" class="hidden absolute top-4 left-4 z-[1000] bg-slate-950/90 border border-slate-800/80 p-4 rounded-2xl max-w-xs shadow-2xl">
         <p class="text-xs text-slate-300 leading-relaxed">ℹ️ لا توجد كراين مفعلة وقريبة حالياً. يمكنك تفعيل حسابات السائقين التجريبيين في أداة توليد السائقين لملء الخريطة!</p>
         <a href="add_demo_drivers.php" class="text-[10px] text-amber-500 font-bold mt-2 inline-block hover:underline">🚀 الذهاب لتوليد السائقين الآن ⬅️</a>
      </div>
    </div>

  </div>

  <!-- Leaflet JS لإدارة الخرائط الجغرافية المفتوحة -->
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
  
  <script>
    var defaultLat = 33.3152; // إحداثيات بغداد الافتراضية
    var defaultLng = 44.3661;
    
    // إعداد الخريطة
    var map = L.map('map', {
        zoomControl: false // سنقوم بإخفائه للحفاظ على جمالية الشاشة
    }).setView([defaultLat, defaultLng], 12);

    // تفعيل طبقة الخريطة المجانية من OpenStreetMap
    var tileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    // إضافة أداة تحكم للتكبير والتصغير بشكل أنيق في الزاوية السفلى
    L.control.zoom({ position: 'bottomright' }).addTo(map);

    var createCustomIcon = function(colorClass, emoji) {
        return L.divIcon({
            html: `<div class="relative flex items-center justify-center w-12 h-12">
                     <div class="absolute inset-0 rounded-full ${colorClass} opacity-20 animate-ping"></div>
                     <div class="relative bg-slate-950 border-2 border-slate-850 rounded-full w-10 h-10 shadow-2xl flex items-center justify-center text-lg">
                       ${emoji}
                     </div>
                   </div>`,
            className: '',
            iconSize: [48, 48],
            iconAnchor: [24, 24],
            popupAnchor: [0, -24]
        });
    };

    var customerIcon = createCustomIcon('bg-green-500', '👤');
    var driverIcon   = createCustomIcon('bg-amber-500', '🚜');

    // إعداد ماركر العميل
    var userMarker = L.marker([defaultLat, defaultLng], { icon: customerIcon, draggable: true })
                      .addTo(map)
                      .bindPopup("<div class='text-center p-1 font-semibold text-slate-900'>موقعك الحالي<br><span class='text-[10px] text-slate-500'>(اسحب الدبوس لتعديل موقع الخدمة يدوياً)</span></div>")
                      .openPopup();

    // جلب وحفظ السائقين المسجلين في الجلسة من PHP
    var rawDrivers = <?= json_encode($drivers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    var markersArray = [];
    var routePolyline = null;
    var nearestDriverId = null;
    var activeRouteDriverId = null;
    var routeRequestToken = 0;

    function haversineDistanceKm(lat1, lon1, lat2, lon2) {
      var earthRadius = 6371;
      var dLat = (lat2 - lat1) * Math.PI / 180;
      var dLon = (lon2 - lon1) * Math.PI / 180;
      var a =
        Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
        Math.sin(dLon / 2) * Math.sin(dLon / 2);
      var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
      return earthRadius * c;
    }

    function getUserPosition() {
      var pos = userMarker.getLatLng();
      return { lat: pos.lat, lng: pos.lng };
    }

    function enrichDriversWithDistance(driversList) {
      var userPos = getUserPosition();

      return driversList.map(function(driver) {
        var distanceKm = haversineDistanceKm(userPos.lat, userPos.lng, parseFloat(driver.latitude), parseFloat(driver.longitude));
        return Object.assign({}, driver, {
          distance_km: distanceKm,
          distance_text: distanceKm < 1
            ? Math.round(distanceKm * 1000) + ' متر'
            : distanceKm.toFixed(2) + ' كم'
        });
      }).sort(function(a, b) {
        return a.distance_km - b.distance_km;
      });
    }

    function clearRouteLine() {
      if (routePolyline) {
        map.removeLayer(routePolyline);
        routePolyline = null;
      }
    }

    function getRouteStyle(driverId) {
      var isNearest = String(driverId) === String(nearestDriverId);
      var isActive = String(driverId) === String(activeRouteDriverId);

      if (isActive) {
        return {
          color: '#38bdf8',
          weight: 6,
          opacity: 0.95,
          dashArray: null
        };
      }

      if (isNearest) {
        return {
          color: '#22c55e',
          weight: 6,
          opacity: 0.85,
          dashArray: '10, 8'
        };
      }

      return {
        color: '#f59e0b',
        weight: 5,
        opacity: 0.8,
        dashArray: '10, 8'
      };
    }

    function drawFallbackRoute(driver, shouldFitBounds) {
      clearRouteLine();

      var userPos = getUserPosition();
      var routeStyle = getRouteStyle(driver.driver_id);
      routePolyline = L.polyline([
        [userPos.lat, userPos.lng],
        [parseFloat(driver.latitude), parseFloat(driver.longitude)]
      ], routeStyle).addTo(map);

      if (shouldFitBounds) {
        map.fitBounds(routePolyline.getBounds(), { padding: [40, 40] });
      }
    }

    async function drawRouteToDriver(driver, shouldFitBounds) {
      clearRouteLine();
      activeRouteDriverId = driver.driver_id;
      renderDriversOnMap(rawDrivers);

      var userPos = getUserPosition();
      var requestToken = ++routeRequestToken;
      var routeUrl = `https://router.project-osrm.org/route/v1/driving/${encodeURIComponent(driver.longitude)},${encodeURIComponent(driver.latitude)};${encodeURIComponent(userPos.lng)},${encodeURIComponent(userPos.lat)}?overview=full&geometries=geojson`;

      try {
        var response = await fetch(routeUrl);
        var data = await response.json();

        if (requestToken !== routeRequestToken) {
          return;
        }

        if (!data.routes || !data.routes.length || !data.routes[0].geometry || !data.routes[0].geometry.coordinates) {
          drawFallbackRoute(driver, shouldFitBounds);
          return;
        }

        var latLngs = data.routes[0].geometry.coordinates.map(function(point) {
          return [point[1], point[0]];
        });

        routePolyline = L.polyline(latLngs, getRouteStyle(driver.driver_id)).addTo(map);

        if (shouldFitBounds) {
          map.fitBounds(routePolyline.getBounds(), { padding: [40, 40] });
        }
      } catch (error) {
        drawFallbackRoute(driver, shouldFitBounds);
      }
    }

    function renderDriversOnMap(driversList) {
      var sortedDrivers = enrichDriversWithDistance(driversList);

        // إزالة الماركرات السابقة لمنع التكرار والبطء
        markersArray.forEach(function(m) { map.removeLayer(m); });
        markersArray = [];

        var listContainer = document.getElementById('driversList');
        listContainer.innerHTML = '';
        
      document.getElementById('driversCount').innerText = sortedDrivers.length + " متاح";

      if (sortedDrivers.length === 0) {
            document.getElementById('noDriversNotice').classList.remove('hidden');
        clearRouteLine();
            listContainer.innerHTML = `
                <div class="p-6 text-center border border-dashed border-slate-800 rounded-2xl bg-slate-950/20">
                  <span class="text-3xl block mb-2">📭</span>
                  <p class="text-xs text-slate-500 leading-relaxed">لا يوجد سائقون متطابقون مع بحثك حالياً.</p>
                </div>
            `;
            return;
        } else {
            document.getElementById('noDriversNotice').classList.add('hidden');
        }

          nearestDriverId = sortedDrivers[0] ? sortedDrivers[0].driver_id : null;

          sortedDrivers.forEach(function(d, index) {
            var isNearest = String(d.driver_id) === String(nearestDriverId);
            var isActiveRoute = String(d.driver_id) === String(activeRouteDriverId);
            var routeLabel = isActiveRoute ? 'المسار النشط المعروض الآن' : (isNearest ? 'أقرب كرين متاح إليك حالياً' : 'يمكن عرض مساره نحوك');
            var routeLabelColor = isActiveRoute ? 'text-sky-600' : (isNearest ? 'text-emerald-600' : 'text-amber-600');
            var updateAgeText = d.minutes_since_update !== undefined
              ? (parseInt(d.minutes_since_update, 10) <= 0 ? 'تحديث الآن' : 'آخر تحديث منذ ' + d.minutes_since_update + ' دقيقة')
              : 'تحديث غير محدد';

            // 1. رسم العلامة الجغرافية على الخريطة
            var marker = L.marker([d.latitude, d.longitude], { icon: driverIcon })
                          .addTo(map)
                          .bindPopup(`
                              <div class="text-right p-1 font-sans">
                                  <h3 class="font-bold text-amber-500 text-sm mb-1">${d.fullname}</h3>
                                  <p class="text-xs text-slate-700 mb-1"><b>📞 الهاتف:</b> ${d.phone}</p>
                                  <p class="text-xs text-slate-700 mb-1"><b>📍 المحافظة:</b> ${d.province}</p>
                        <p class="text-xs text-slate-700 mb-1"><b>📏 المسافة:</b> ${d.distance_text}</p>
                        <p class="text-xs text-slate-700 mb-1"><b>🚘 رقم اللوحة:</b> ${d.wheel_number || 'غير متوفر'}</p>
                        <p class="text-xs text-slate-700 mb-1"><b>🎨 اللون:</b> ${d.wheel_color || 'غير محدد'}</p>
                        <p class="text-xs text-slate-700 mb-1"><b>🧩 الموديل:</b> ${d.wheel_model || 'غير محدد'}</p>
                                  <p class="text-xs text-slate-700 mb-1"><b>🚜 نوع الكرين:</b> ${d.wheel_type || 'سطحة هيدروليكية'}</p>
                        <p class="text-xs ${routeLabelColor} font-bold mb-1"><b>🛣️ حالة الطريق:</b> ${routeLabel}</p>
                        <p class="text-xs text-slate-500 mb-1"><b>🕒 الحالة:</b> ${updateAgeText}</p>
                        <button type="button" class="route-to-me-btn block w-full text-center bg-amber-500 text-slate-950 text-xs font-bold py-1.5 rounded-lg mt-2 transition hover:bg-amber-600" data-driver-id="${d.driver_id}">عرض طريق تحركه نحوي</button>
                                  <a href="tel:${d.phone}" class="block text-center bg-green-600 text-white text-xs font-bold py-1.5 rounded-lg mt-2 transition hover:bg-green-750">اتصال فوري 📞</a>
                              </div>
                          `);
            marker.driverInfo = d;
            marker.on('click', function() {
              drawRouteToDriver(d, true);
            });
            markersArray.push(marker);

            // 2. إضافة السائق كبطاقة تفاعلية ممتازة في القائمة الجانبية
            var card = document.createElement('div');
            card.className = "p-4 bg-slate-950/60 hover:bg-slate-950 rounded-2xl border cursor-pointer transition-all duration-150 flex items-center justify-between group " +
                (isActiveRoute
                    ? "border-sky-400/50 ring-1 ring-sky-400/30"
                    : isNearest
                    ? "border-emerald-500/40 ring-1 ring-emerald-500/30"
                    : "border-slate-800/80 hover:border-amber-500/40");
            card.innerHTML = `
                <div class="space-y-1 flex-1">
                  <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-green-500"></span>
                    <h4 class="text-xs font-bold text-white group-hover:text-amber-500 transition">${d.fullname}</h4>
                    ${isNearest ? '<span class="rounded-full bg-emerald-500/10 px-2 py-0.5 text-[9px] font-bold text-emerald-400 border border-emerald-500/20">الأقرب</span>' : ''}
                    ${isActiveRoute ? '<span class="rounded-full bg-sky-500/10 px-2 py-0.5 text-[9px] font-bold text-sky-300 border border-sky-500/20">المسار النشط</span>' : ''}
                  </div>
                  <p class="text-[10px] text-slate-400">📍 المحافظة: ${d.province} | 🚜 ${d.wheel_type || 'سطحة'}</p>
                  <p class="text-[10px] font-mono text-slate-500">رقم اللوحة: ${d.wheel_number || 'غير متوفر'}</p>
                  <p class="text-[10px] text-slate-500">📞 الهاتف: ${d.phone || 'غير متوفر'} | 🎨 ${d.wheel_color || 'غير محدد'} | 🧩 ${d.wheel_model || 'غير محدد'}</p>
                  <p class="text-[10px] font-bold ${isActiveRoute ? 'text-sky-300' : isNearest ? 'text-emerald-400' : 'text-amber-400'}">المسافة منك: ${d.distance_text}</p>
                  <p class="text-[10px] ${isActiveRoute ? 'text-sky-400' : isNearest ? 'text-emerald-400' : 'text-slate-400'}">${routeLabel}</p>
                  <p class="text-[10px] text-slate-500">🕒 ${updateAgeText}</p>
                </div>
                <span class="text-xs text-amber-500 group-hover:translate-x-[-4px] transition duration-200">🔍</span>
            `;
            
            // عند نقر البطاقة، تطير الخريطة لموقع السائق المختار وتفتح تفاصيله
            card.addEventListener('click', function() {
                drawRouteToDriver(d, true);
                marker.openPopup();
            });

            listContainer.appendChild(card);

            if (index === 0 && activeRouteDriverId === null) {
                drawRouteToDriver(d, false);
            }
        });
    }

    // التشغيل الأولي لرسم السائقين
    renderDriversOnMap(rawDrivers);

    function filterDrivers() {
        var query = document.getElementById('driverSearch').value.trim().toLowerCase();
        var filtered = rawDrivers.filter(function(d) {
            return d.fullname.toLowerCase().includes(query) || 
                   d.province.toLowerCase().includes(query) || 
                   (d.wheel_type && d.wheel_type.toLowerCase().includes(query));
        });
        renderDriversOnMap(filtered);
    }

    function searchLocation() {
        var query = document.getElementById('geoInput').value.trim();
        if (!query) return;

        var url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query + ', Iraq')}&limit=1`;

        fetch(url)
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data && data.length > 0) {
                    var lat = parseFloat(data[0].lat);
                    var lon = parseFloat(data[0].lon);

                    map.flyTo([lat, lon], 14, { animate: true, duration: 1.5 });
                    userMarker.setLatLng([lat, lon])
                              .bindPopup(`<div class='text-center p-1 font-semibold text-slate-900'>موقع البحث: ${query}</div>`)
                              .openPopup();
                    renderDriversOnMap(rawDrivers);
                } else {
                    // بديل آمن وسريع لعرض لافتة التنبيه دون استخدام الـ alert
                    var geoBtn = document.getElementById('geoInput');
                    geoBtn.value = '';
                    geoBtn.placeholder = "❌ عذراً، لم نعثر على المنطقة.";
                    setTimeout(() => { geoBtn.placeholder = "مثال: المنصور، بغداد..."; }, 3000);
                }
            })
            .catch(function(err) {
                console.error("Nominatim Lookup Error: ", err);
            });
    }

    let userWatchId = null;
    let bestCustomerFix = null;
    let customerAccuracyTarget = 35;

    function fetchGPSLocation(centerOnUser) {
        if (!navigator.geolocation) {
            console.warn("المتصفح لا يدعم تحديد الموقع");
            return;
        }

        // إيقاف watchPosition السابق لتجنب التكرار
        if (userWatchId !== null) {
            navigator.geolocation.clearWatch(userWatchId);
        }

        // watchPosition - يتحدث فور أي تحرك بدون الحاجة لضغط زر
        userWatchId = navigator.geolocation.watchPosition(
            function(pos) {
                var lat      = pos.coords.latitude;
                var lng      = pos.coords.longitude;
                var accuracy = Math.round(pos.coords.accuracy);

            if (!bestCustomerFix || accuracy < bestCustomerFix.accuracy) {
              bestCustomerFix = { lat: lat, lng: lng, accuracy: accuracy };
            }

            var chosenFix = bestCustomerFix || { lat: lat, lng: lng, accuracy: accuracy };

            userMarker.setLatLng([chosenFix.lat, chosenFix.lng]);
            renderDriversOnMap(rawDrivers);

                if (centerOnUser) {
              map.flyTo([chosenFix.lat, chosenFix.lng], chosenFix.accuracy <= customerAccuracyTarget ? 16 : 15, { animate: true, duration: 1.2 });
                    userMarker.openPopup();
                }

                // تحديث مؤشر الدقة إن وُجد
                let gpsStatus = document.getElementById('gpsStatus');
                if (gpsStatus) {
                  gpsStatus.innerText = "📍 أفضل دقة حالية: " + chosenFix.accuracy + "م";
                  gpsStatus.className = chosenFix.accuracy <= 20
                    ? "text-xs text-emerald-400 font-bold"
                    : chosenFix.accuracy <= 50
                    ? "text-xs text-green-400 font-bold"
                    : chosenFix.accuracy <= 120
                    ? "text-xs text-yellow-400 font-bold"
                    : "text-xs text-red-400 font-bold";
                }
            },
            function(err) {
                let gpsStatus = document.getElementById('gpsStatus');
                if (gpsStatus) {
                    gpsStatus.innerText = "❌ تعذّر تحديد موقعك";
                    gpsStatus.className = "text-xs text-red-400 font-bold";
                }
                console.warn("⚠️ GPS:", err.message);
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0  // لا يقبل موقعاً محفوظاً - دائماً موقع جديد
            }
        );
    }

    // زر موقعي الحالي - يُركّز الخريطة على المستخدم
    document.getElementById('gpsBtn').addEventListener('click', function() {
        fetchGPSLocation(true);
    });

    // تشغيل watchPosition فور تحميل الصفحة (يتحدث تلقائياً عند تغير الموقع)
    fetchGPSLocation(false);

    userMarker.on('dragend', function() {
      renderDriversOnMap(rawDrivers);
    });

    document.addEventListener('click', function(event) {
      var routeBtn = event.target.closest('.route-to-me-btn');
      if (!routeBtn) {
        return;
      }

      var driverId = routeBtn.getAttribute('data-driver-id');
      var driver = rawDrivers.find(function(item) {
        return String(item.driver_id) === String(driverId);
      });

      if (driver) {
        drawRouteToDriver(driver, true);
      }
    });

    document.getElementById('themeToggle').addEventListener('click', function() {
        document.getElementById('map').classList.toggle('dark-map-style');
    });
  </script>
</body>
</html>
