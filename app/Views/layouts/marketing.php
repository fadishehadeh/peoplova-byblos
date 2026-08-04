<?php declare(strict_types=1);
$canonicalUrl = rtrim(url('/'), '/') . '/';
$ogImage      = url('/assets/images/Byblos HR-og.png');
$pageTitle    = $title ?? (\App\Support\Branding::name() . ' — HR Operations Platform for People-Led Teams');
$pageDesc     = $metaDescription ?? 'Byblos HR is an implementation-led HR operations platform that centralises employee records, leave, documents, onboarding, offboarding, and recruitment into one structured workspace — delivered through a guided rollout. Built for GCC and Arab World organizations.';
$pageKeywords = $metaKeywords ?? 'HR platform Qatar, HR software UAE, Gulf HR platform, HR system GCC, people operations, employee management system, leave management Gulf, HR onboarding software, HRIS Arabic, نظام موارد بشرية, HR software Middle East, workforce management Qatar';
?>
<!doctype html>
<html lang="en" prefix="og: https://ogp.me/ns#">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#8154FF">
    <link rel="icon" href="<?= e(url('/favicon.svg')); ?>" type="image/svg+xml">
    <link rel="shortcut icon" href="<?= e(url('/favicon.svg')); ?>">

    <title><?= e($pageTitle); ?></title>
    <meta name="description" content="<?= e($pageDesc); ?>">
    <meta name="keywords" content="<?= e($pageKeywords); ?>">
    <meta name="author" content="<?= e(\App\Support\Branding::name()); ?>">
    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <link rel="canonical" href="<?= e($canonicalUrl); ?>">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= e(\App\Support\Branding::name()); ?>">
    <meta property="og:title" content="<?= e($pageTitle); ?>">
    <meta property="og:description" content="<?= e($pageDesc); ?>">
    <meta property="og:url" content="<?= e($canonicalUrl); ?>">
    <meta property="og:image" content="<?= e($ogImage); ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="<?= e(\App\Support\Branding::name()); ?> HR Platform">
    <meta property="og:locale" content="en_US">

    <!-- Twitter / X Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($pageTitle); ?>">
    <meta name="twitter:description" content="<?= e($pageDesc); ?>">
    <meta name="twitter:image" content="<?= e($ogImage); ?>">

    <!-- JSON-LD Structured Data (SEO + AIO + GEO) -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@graph": [
        {
          "@type": "WebSite",
          "@id": "<?= e(rtrim(url('/'), '/')); ?>/#website",
          "url": "<?= e($canonicalUrl); ?>",
          "name": "<?= e(\App\Support\Branding::name()); ?>",
          "description": "<?= e($pageDesc); ?>",
          "inLanguage": "en-US"
        },
        {
          "@type": "Organization",
          "@id": "<?= e(rtrim(url('/'), '/')); ?>/#organization",
          "name": "<?= e(\App\Support\Branding::name()); ?>",
          "url": "<?= e($canonicalUrl); ?>",
          "logo": {
            "@type": "ImageObject",
            "url": "<?= e(url('/assets/images/Byblos HR-mark.svg')); ?>",
            "width": 32,
            "height": 32
          },
          "description": "Byblos HR is an implementation-led HR operations platform that centralises employee lifecycle management, leave, documents, onboarding, offboarding, and recruitment into one structured workspace.",
          "knowsAbout": ["Human Resources", "People Operations", "Employee Management", "HRIS", "Leave Management", "Workforce Management"]
        },
        {
          "@type": "SoftwareApplication",
          "@id": "<?= e(rtrim(url('/'), '/')); ?>/#product",
          "name": "<?= e(\App\Support\Branding::name()); ?> HR Platform",
          "applicationCategory": "BusinessApplication",
          "applicationSubCategory": "Human Resources Software",
          "operatingSystem": "Web",
          "url": "<?= e($canonicalUrl); ?>",
          "description": "A complete HR operations platform covering employee records, leave approvals, document management, onboarding workflows, offboarding checklists, recruitment pipelines, and HR reporting — configured to your organisation through a guided rollout.",
          "featureList": [
            "Employee records and lifecycle management",
            "Leave requests and approval workflows",
            "HR document management with expiry tracking",
            "Onboarding and offboarding task workflows",
            "Recruitment and careers portal",
            "Role-based access control",
            "HR reporting and analytics",
            "Announcement and notification system",
            "Org chart and structure management",
            "Payroll structure management"
          ],
          "offers": {
            "@type": "Offer",
            "description": "Contact for pricing. Delivered through a guided implementation engagement."
          },
          "provider": {
            "@id": "<?= e(rtrim(url('/'), '/')); ?>/#organization"
          }
        },
        {
          "@type": "FAQPage",
          "@id": "<?= e(rtrim(url('/'), '/')); ?>/#faq",
          "mainEntity": [
            {
              "@type": "Question",
              "name": "What is Byblos HR?",
              "acceptedAnswer": {
                "@type": "Answer",
                "text": "Byblos HR is an HR operations platform built for HR leaders who need one structured system to manage employee records, leave approvals, documents, onboarding, offboarding, and recruitment. It is delivered through a guided implementation so the setup matches your organisation's structure and processes."
              }
            },
            {
              "@type": "Question",
              "name": "What HR modules does Byblos HR include?",
              "acceptedAnswer": {
                "@type": "Answer",
                "text": "Byblos HR includes modules for employee records and lifecycle tracking, leave requests and multi-level approvals, HR document management with expiry alerts, onboarding and offboarding workflows, a recruitment and careers portal, org chart visualisation, HR reporting, announcements, notifications, and payroll structure management."
              }
            },
            {
              "@type": "Question",
              "name": "How is Byblos HR implemented?",
              "acceptedAnswer": {
                "@type": "Answer",
                "text": "Byblos HR is delivered through a guided rollout rather than a self-serve setup. The implementation is configured to match your organisation's structure, approval model, access permissions, and branding before handoff — reducing the gap between purchase and productive use."
              }
            },
            {
              "@type": "Question",
              "name": "Who is Byblos HR designed for?",
              "acceptedAnswer": {
                "@type": "Answer",
                "text": "Byblos HR is built for HR leaders and operations stakeholders at organisations that need better control over people workflows, compliance exposure, and decision visibility. It is particularly suited to teams currently managing HR across spreadsheets, email inboxes, and disconnected tools."
              }
            },
            {
              "@type": "Question",
              "name": "Does Byblos HR support multiple departments and branches?",
              "acceptedAnswer": {
                "@type": "Answer",
                "text": "Yes. Byblos HR supports multi-branch and multi-department org structures with role-based access control, allowing different permission levels for HR administrators, managers, and employees across locations."
              }
            },
            {
              "@type": "Question",
              "name": "How do I get started with Byblos HR?",
              "acceptedAnswer": {
                "@type": "Answer",
                "text": "To get started with Byblos HR, use the contact form on the website to send an enquiry. The team will follow up to assess fit, discuss your structure, and outline a rollout approach tailored to your organisation."
              }
            }
          ]
        }
      ]
    }
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@700;800&display=swap" rel="stylesheet">
    <link href="<?= e(asset('css/bootstrap.min.css')); ?>" rel="stylesheet">
    <link href="<?= e(asset('css/bootstrap-icons.min.css')); ?>" rel="stylesheet">
    <link href="<?= e(asset('css/app.css')); ?>" rel="stylesheet">
    <link href="<?= e(asset('css/marketing.css')); ?>" rel="stylesheet">
</head>
<body class="marketing-body">
    <?php require base_path('app/Views/partials/flash.php'); ?>
    <?= $content; ?>
    <script src="<?= e(asset('js/bootstrap.bundle.min.js')); ?>"></script>
</body>
</html>

