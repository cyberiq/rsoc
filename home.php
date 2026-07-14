<?php
session_start();
$user_id = $_SESSION['user_id'] ?? null;
$user_type = $_SESSION['user_type'] ?? null;
$fullname = $_SESSION['fullname'] ?? $_SESSION['user_name'] ?? '';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>تطبيق كرين - خدمات السحب والإنقاذ السريع في العراق</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap');
        body { font-family: 'Cairo', sans-serif; }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen">

    <!-- Header Navigation -->
    <header class="sticky top-0 z-50 border-b border-slate-800 bg-slate-900/90 backdrop-blur-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <div class="flex items-center gap-3">
                    <span class="text-3xl animate-bounce">🚜</span>
                    <div>
                        <h1 class="text-lg font-black text-white leading-none">تطبيق <span class="text-amber-500">كرين</span></h1>
                        <p class="text-[9px] text-slate-400 mt-1 font-bold">الإنقاذ السريع في العراق</p>
                    </div>
                </div>
                
                <!-- القائمة الرئيسية للشاشات الكبيرة -->
                <nav class="hidden md:flex items-center gap-8 text-xs font-bold text-slate-300">
                    <a href="#about" class="hover:text-amber-500 transition">عن التطبيق</a>
                    <a href="#features" class="hover:text-amber-500 transition">المميزات</a>
                    <a href="#how-it-works" class="hover:text-amber-500 transition">كيف يعمل؟</a>
                    <a href="#faq" class="hover:text-amber-500 transition">الأسئلة الشائعة</a>
                    <a href="#contact" class="hover:text-amber-500 transition">اتصل بنا</a>
                </nav>
                
                <div class="flex items-center gap-4">
                    <?php if ($user_id): ?>
                        <div class="flex items-center gap-3">
                            <span class="text-xs text-slate-400 hidden sm:inline">أهلاً، <span class="text-white font-bold"><?= htmlspecialchars($fullname) ?></span></span>
                            <a href="dashboard.php" class="bg-amber-500 text-slate-950 px-5 py-2.5 rounded-xl text-xs font-black">لوحة التحكم 🚀</a>
                        </div>
                    <?php else: ?>
                        <a href="choose_login.php" class="bg-slate-900 border border-slate-800 hover:border-amber-500 px-5 py-2.5 rounded-xl text-xs font-bold text-white transition">تسجيل الدخول</a>
                        <a href="choose_action.php?action=register" class="bg-amber-500 text-slate-950 px-5 py-2.5 rounded-xl text-xs font-black">سجل الآن مجاناً</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <main class="relative">
        
        <!-- Hero Section -->
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 text-center space-y-6">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-amber-500/10 text-amber-500 border border-amber-500/20">
                🚨 متواجدون لإنقاذك في كافة محافظات العراق وعلى مدار 24 ساعة
            </span>
            <h2 class="text-4xl sm:text-6xl font-black text-white leading-tight">
                عجلتك معطلة؟ نصلك بأسرع وقت <br class="hidden sm:inline">وبأحدث <span class="text-amber-500">كرينات الإنقاذ</span>
            </h2>
            <p class="text-slate-400 text-sm sm:text-base max-w-2xl mx-auto leading-relaxed">
                منصة كرين تجمع أصحاب العجلات المعطلة بأقرب ونش أو كيا حمل في العراق. حدد موقعك بدقة، اطلب الخدمة بضغطة زر وتتبع بطل الإنقاذ مباشرة على الخريطة.
            </p>
            <div class="pt-4 flex flex-col sm:flex-row justify-center items-center gap-4">
                <a href="choose_login.php" class="w-full sm:w-auto px-8 py-4 bg-amber-500 text-slate-950 font-black rounded-xl text-sm">
                    طلب كرين إنقاذ عاجل 🚚
                </a>
                <a href="choose_action.php?action=register" class="w-full sm:w-auto px-8 py-4 bg-slate-900 border border-slate-800 hover:border-slate-700 text-white font-bold rounded-xl text-sm transition">
                    تسجيل حساب مزود خدمة (سائق)
                </a>
            </div>
        </section>

        <!-- Stats Section (KPIs) -->
        <section class="border-y border-slate-900 bg-slate-900/40 py-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                <div class="space-y-1">
                    <span class="block text-3xl sm:text-4xl font-black text-white">+12,000</span>
                    <span class="text-xs text-slate-500">عملية إنقاذ وسحب ناجحة</span>
                </div>
                <div class="space-y-1">
                    <span class="block text-3xl sm:text-4xl font-black text-amber-500">+500</span>
                    <span class="text-xs text-slate-500">سائق كرين وكيا معتمد</span>
                </div>
                <div class="space-y-1">
                    <span class="block text-3xl sm:text-4xl font-black text-white">8 دقائق</span>
                    <span class="text-xs text-slate-500">متوسط وقت الاستجابة والوصول</span>
                </div>
                <div class="space-y-1">
                    <span class="block text-3xl sm:text-4xl font-black text-amber-500">18 محافظة</span>
                    <span class="text-xs text-slate-500">تغطية شاملة لكل ربوع العراق</span>
                </div>
            </div>
        </section>

        <!-- About Section -->
        <section id="about" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 space-y-16">
            <div class="text-center space-y-3">
                <h3 class="text-xs font-black text-amber-500 uppercase tracking-wider">مكونات نظام كرين</h3>
                <h2 class="text-3xl font-extrabold text-white">منظومة ذكية تجمع كافة الأطراف في العراق</h2>
                <p class="text-xs text-slate-400 max-w-xl mx-auto leading-relaxed">قمنا ببناء بيئة مخصصة تضمن الكفاءة، السرعة، والأمان لجميع المستخدمين والسائقين لضمان رحلة إنقاذ مثالية.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Card 1: Customer -->
                <div class="bg-slate-900/60 p-8 rounded-3xl border border-slate-800 space-y-4">
                    <span class="text-4xl p-3 bg-green-500/10 text-green-500 rounded-2xl inline-block">👤</span>
                    <h4 class="text-lg font-black text-white">المستخدم العادي (العميل)</h4>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        يستطيع العميل طلب أقرب كرين أو كيا بشكل فوري، وتتبع موقع السائق على الخريطة حياً لمنع الانتظار العشوائي على الطرقات.
                    </p>
                    <a href="choose_login.php?user_type=customer" class="text-xs text-green-500 font-bold inline-flex items-center gap-1 hover:underline">طلب الخدمة الآن ⬅️</a>
                </div>

                <!-- Card 2: Crane Driver -->
                <div class="bg-slate-900/60 p-8 rounded-3xl border border-slate-800 space-y-4">
                    <span class="text-4xl p-3 bg-amber-500/10 text-amber-500 rounded-2xl inline-block">🚜</span>
                    <h4 class="text-lg font-black text-white">سائق الكرين (الونش)</h4>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        يتيح لجميع سواق الكراين (السطحة، التلسكوبي) استقبال طلبات سحب وإنقاذ السيارات المتضررة في نطاق محافظتهم وتحقيق دخل ممتاز.
                    </p>
                    <a href="choose_login.php?user_type=driver" class="text-xs text-amber-500 font-bold inline-flex items-center gap-1 hover:underline">انضم كـسائق كرين ⬅️</a>
                </div>

                <!-- Card 3: Kia Driver -->
                <div class="bg-slate-900/60 p-8 rounded-3xl border border-slate-800 space-y-4">
                    <span class="text-4xl p-3 bg-sky-500/10 text-sky-400 rounded-2xl inline-block">🚚</span>
                    <h4 class="text-lg font-black text-white">سائق كيا حمل</h4>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        مخصص لسائقي سيارات الكيا واللوريات لنقل الأحمال، المواد، أو ترحيل المحركات والسيارات الخفيفة لخدمة عملاء التطبيق بمرونة تامة.
                    </p>
                    <a href="choose_login.php?user_type=kia" class="text-xs text-sky-400 font-bold inline-flex items-center gap-1 hover:underline">انضم كـسائق كيا ⬅️</a>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section id="features" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 border-t border-slate-900">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div class="space-y-6">
                    <span class="text-xs font-black text-amber-500 uppercase tracking-wider">لماذا تطبيق كرين؟</span>
                    <h2 class="text-3xl font-extrabold text-white leading-snug">صممنا حلولاً ذكية تنهي عهد العشوائية في سحب السيارات</h2>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        نعلم جيداً مدى التوتر والقلق عند تعطل عجلتكم في وسط الطريق. لذلك يجمع تطبيق كرين كل مميزات التقنية الجغرافية لخدمتكم بسرعة فائقة وموثوقية عراقية 100%.
                    </p>
                    
                    <div class="space-y-4 pt-4">
                        <div class="flex items-start gap-4">
                            <span class="text-xl p-2 bg-slate-900 border border-slate-800 text-amber-500 rounded-xl">📍</span>
                            <div>
                                <h4 class="text-xs font-bold text-white">تحديد موقع جغرافي فوري بالـ GPS</h4>
                                <p class="text-[11px] text-slate-500 mt-0.5">لا تحتاج لشرح مكانك بعد اليوم، رادار المنصة يتعرف على موقعك الفعلي بدقة تامة على الخريطة.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <span class="text-xl p-2 bg-slate-900 border border-slate-800 text-amber-500 rounded-xl">🛡️</span>
                            <div>
                                <h4 class="text-xs font-bold text-white">توثيق كامل للسلامة والخصوصية</h4>
                                <p class="text-[11px] text-slate-500 mt-0.5">نطابق لوحة المركبة، وصور السائق، وتفاصيل الونش لحماية خصوصية العوائل وأمن الطرقات.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <span class="text-xl p-2 bg-slate-900 border border-slate-800 text-amber-500 rounded-xl">⚙️</span>
                            <div>
                                <h4 class="text-xs font-bold text-white">تفعيل سريع وآمن للبريد (OTP)</h4>
                                <p class="text-[11px] text-slate-500 mt-0.5">نعتمد نظام التحقق بالبريد الإلكتروني الذكي لضمان حماية الحسابات من الرسائل العشوائية والسبام.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- خريطة توضيحية بسيطة -->
                <div class="bg-slate-900 border border-slate-800 p-6 rounded-3xl space-y-4 shadow-2xl">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-green-500 animate-pulse"></span>
                            <span class="text-xs text-slate-300 font-bold">نشاط الخريطة الجغرافية</span>
                        </div>
                    </div>
                    <div class="h-64 rounded-2xl bg-slate-950 flex flex-col items-center justify-center text-center p-4 border border-slate-800 space-y-4">
                        <span class="text-5xl">🗺️</span>
                        <p class="text-xs text-slate-400 leading-relaxed max-w-xs">
                            يمكنك رؤية الكراين النشطة بالقرب منك في لوحة التحكم وتوجيه طلبك فوراً.
                        </p>
                        <a href="choose_login.php" class="px-5 py-2.5 bg-amber-500 text-slate-950 font-black rounded-xl text-[11px]">
                            تتبع مواقع الكراين حياً ⬅️
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- How It Works Section -->
        <section id="how-it-works" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 space-y-16">
            <div class="text-center space-y-3">
                <span class="text-xs font-black text-amber-500 uppercase tracking-wider">بساطة الاستخدام</span>
                <h2 class="text-3xl font-extrabold text-white">كيف تطلب كرين بـ 3 خطوات فقط؟</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Step 1 -->
                <div class="bg-slate-900/40 p-8 rounded-3xl border border-slate-800/60 space-y-3 text-center relative">
                    <span class="text-4xl font-black text-amber-500/20 absolute top-4 right-6">01</span>
                    <span class="text-4xl block pt-4">📍</span>
                    <h4 class="text-sm font-bold text-white pt-2">حدد موقع تعطل عجلتك</h4>
                    <p class="text-[11px] text-slate-400 leading-relaxed">
                        ادخل للوحة الخدمة، حدد موقعك الجغرافي بالـ GPS بدبوس دقيق على الخريطة المدمجة ليعرف السائق مكانك تماماً.
                    </p>
                </div>

                <!-- Step 2 -->
                <div class="bg-slate-900/40 p-8 rounded-3xl border border-slate-800/60 space-y-3 text-center relative">
                    <span class="text-4xl font-black text-amber-500/20 absolute top-4 right-6">02</span>
                    <span class="text-4xl block pt-4">🚀</span>
                    <h4 class="text-sm font-bold text-white pt-2">أرسل طلب السحب الفوري</h4>
                    <p class="text-[11px] text-slate-400 leading-relaxed">
                        بمجرد تأكيد الطلب، سيقوم رادار النظام بنشر وتعميم طلب الإنقاذ على كل السواق المتواجدين بالقرب منك.
                    </p>
                </div>

                <!-- Step 3 -->
                <div class="bg-slate-900/40 p-8 rounded-3xl border border-slate-800/60 space-y-3 text-center relative">
                    <span class="text-4xl font-black text-amber-500/20 absolute top-4 right-6">03</span>
                    <span class="text-4xl block pt-4">🤝</span>
                    <h4 class="text-sm font-bold text-white pt-2">تم القبول ومتابعة التحرك</h4>
                    <p class="text-[11px] text-slate-400 leading-relaxed">
                        سيقوم السائق الأقرب بقبول طلبك فوراً، وستظهر لك تفاصيله وصورته ورقم لوحة الكرين ورقم هاتفه للاتصال الفوري.
                    </p>
                </div>
            </div>
        </section>

        <!-- FAQs Section -->
        <section id="faq" class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-20 border-t border-slate-900">
            <div class="text-center space-y-3 mb-12">
                <span class="text-xs font-black text-amber-500 uppercase tracking-wider">أجوبة سريعة</span>
                <h2 class="text-2xl font-black text-white">الأسئلة الشائعة حول تطبيق كرين</h2>
            </div>

            <div class="space-y-4">
                <!-- Q1 -->
                <details class="group bg-slate-900/60 border border-slate-800 rounded-2xl p-5 cursor-pointer [&_summary::-webkit-details-marker]:hidden">
                    <summary class="flex justify-between items-center text-xs font-bold text-white">
                        <span>❓ هل التطبيق مجاني للتسجيل والاستخدام؟</span>
                        <span class="transition duration-300 group-open:-rotate-180 text-amber-500">▼</span>
                    </summary>
                    <p class="text-[11px] text-slate-400 mt-3 leading-relaxed">
                        نعم، تطبيق كرين مجاني بالكامل لجميع العملاء للتسجيل وطلب الخدمات وتتبع السائقين الجغرافي دون أي رسوم اشتراك شهرية.
                    </p>
                </details>

                <!-- Q2 -->
                <details class="group bg-slate-900/60 border border-slate-800 rounded-2xl p-5 cursor-pointer [&_summary::-webkit-details-marker]:hidden">
                    <summary class="flex justify-between items-center text-xs font-bold text-white">
                        <span>❓ كيف يمكنني التسجيل كسائق وتفعيل حسابي في التطبيق؟</span>
                        <span class="transition duration-300 group-open:-rotate-180 text-amber-500">▼</span>
                    </summary>
                    <p class="text-[11px] text-slate-400 mt-3 leading-relaxed">
                        يمكنك الانتقال لصفحة "إنشاء حساب" واختيار نوع الحساب "سائق كرين" أو "سائق كيا"، واملأ معلومات مركبتك ورقم اللوحة، ثم استخدم كود التفعيل المستلم على بريدك الإلكتروني لتنشيط حسابك مباشرة.
                    </p>
                </details>

                <!-- Q3 -->
                <details class="group bg-slate-900/60 border border-slate-800 rounded-2xl p-5 cursor-pointer [&_summary::-webkit-details-marker]:hidden">
                    <summary class="flex justify-between items-center text-xs font-bold text-white">
                        <span>❓ هل تتوفر الخدمات خارج العاصمة بغداد؟</span>
                        <span class="transition duration-300 group-open:-rotate-180 text-amber-500">▼</span>
                    </summary>
                    <p class="text-[11px] text-slate-400 mt-3 leading-relaxed">
                        بالتأكيد! تطبيق كرين يغطي كافة المحافظات العراقية (البصرة، الموصل، أربيل، النجف، كربلاء، بابل، وغيرها) بفضل تواجد أبطال السحب في كل مكان.
                    </p>
                </details>
            </div>
        </section>

        <!-- Contact Section -->
        <section id="contact" class="max-w-2xl mx-auto bg-slate-900/60 p-8 rounded-3xl border border-slate-800 mt-20 mb-24 relative overflow-hidden">
            <h3 class="text-2xl font-bold text-white mb-2 text-center">اتصل بفريق الدعم</h3>
            <p class="text-slate-400 text-xs text-center mb-8">هل لديك استفسار أو مشكلة في الحساب؟ راسلنا وسيقوم فريقنا بالرد الفوري.</p>
            
            <form id="contactForm" onsubmit="handleContactSubmit(event)" class="space-y-6">
                <div id="contactSuccess" class="hidden p-4 rounded-xl bg-green-500/10 text-green-400 border border-green-500/20 text-center text-xs font-bold"></div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <input type="text" id="contactName" placeholder="الاسم الكامل" required 
                           class="w-full p-3.5 bg-slate-950 rounded-xl border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-amber-500 transition">
                    <input type="tel" id="contactPhone" placeholder="رقم الهاتف" required 
                           class="w-full p-3.5 bg-slate-950 rounded-xl border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-amber-500 transition text-left font-mono">
                </div>

                <input type="email" id="contactEmail" placeholder="البريد الإلكتروني (Gmail)" required
                       class="w-full p-3.5 bg-slate-950 rounded-xl border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-amber-500 transition text-left font-mono">
                
                <textarea id="contactMessage" placeholder="كيف يمكننا مساعدتك؟ يرجى توضيح طلبك هنا..." required 
                          class="w-full p-3.5 bg-slate-950 rounded-xl border border-slate-800 text-xs text-white placeholder-slate-500 h-32 focus:outline-none focus:border-amber-500 transition"></textarea>
                
                <button type="submit" class="w-full py-4 bg-amber-500 text-slate-950 font-black rounded-xl text-xs">
                    🚀 إرسال الرسالة بنجاح
                </button>
            </form>
        </section>

    </main>

    <!-- Footer -->
    <footer class="py-10 border-t border-slate-900 bg-slate-900/80 text-center relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <span class="text-3xl">🚜</span>
            <h4 class="text-xs font-bold text-white">تطبيق كرين العراق لخدمات السحب والإنقاذ</h4>
            <p class="text-[10px] text-slate-500 max-w-md mx-auto leading-relaxed">
                جميع الاتصالات الجغرافية وحسابات المستخدمين مشفرة بالكامل. يخضع هذا النظام لبنود الأمان والموثوقية العراقية لحماية الخصوصية.
            </p>
            <hr class="border-slate-900 w-1/4 mx-auto my-4" />
            <p class="text-[10px] text-slate-600">حقوق الطبع محفوظة © <?= date('Y') ?> لـ <span class="text-amber-500 font-bold">تطبيق كرين</span>. تم التطوير والتهيئة بفخر 🇮🇶</p>
        </div>
    </footer>

    <script>
    function handleContactSubmit(event) {
        event.preventDefault();
        const successEl = document.getElementById('contactSuccess');
        successEl.innerText = "⏳ جاري إرسال الرسالة...";
        successEl.classList.remove('hidden');

        const formData = new FormData();
        formData.append('name', document.getElementById('contactName').value);
        formData.append('phone', document.getElementById('contactPhone').value);
        formData.append('email', document.getElementById('contactEmail').value);
        formData.append('message', document.getElementById('contactMessage').value);

        fetch('contact_process.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            successEl.innerText = data.message;
            if(data.success) {
                document.getElementById('contactForm').reset();
            }
            setTimeout(() => successEl.classList.add('hidden'), 5000);
        })
        .catch(error => {
            console.error('Error:', error);
            successEl.innerText = "❌ حدث خطأ غير متوقع أثناء الإرسال.";
            setTimeout(() => successEl.classList.add('hidden'), 5000);
        });
    }
    </script>
</body>
</html>
