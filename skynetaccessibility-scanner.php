<?php
/*
Plugin Name: SkynetAccessibility Scanner
Description: Scan, monitor, and identify website accessibility issues across WCAG 2.0, 2.1, 2.2, ADA, and EAA.
Version: 1.0
Author: Skynet Technologies USA LLC
Requires PHP: 8.0
Requires CP: 2.2
Author URI: https://www.skynettechnologies.com
Plugin URI: https://github.com/skynettechnologies/classicpress-skynetaccessibility-scanner
Text Domain: skynetaccessibilityscanner
Domain Path: /languages
 ------------------------------------------------------------------------------
 Scan, monitor, and identify website accessibility issues across WCAG 2.0, 2.1, 2.2, ADA, Section 508, EN 301 549, UK Equality Act, Australian DDA, and Canada ACA. Get simple issue highlights with recommended fixes.
 ------------------------------------------------------------------------------
*/

if (!defined('ABSPATH')) { exit; }

add_action('admin_menu', function () {
    add_menu_page(
        'SkynetAccessibility Scanner',
        'SkynetAccessibility Scanner',
        'manage_options',
        'skynetaccessibility-scanner',
        'skynetaccessibility_scanner_settings_page',
        'dashicons-search'
    );
});

function skynetaccessibility_scanner_settings_page() {
   
    $current_user = wp_get_current_user();

    $username = $current_user->user_login;
    $email    = $current_user->user_email;

    // echo 'Username: ' . esc_html($username);
    // echo '<br>';
    // echo 'Email: ' . esc_html($email);
    // scanning & monitoring code start
    // Add user domain
      
    $websitename =  $_SERVER['HTTP_HOST'];
    $arrDetails = [
        'website'        => base64_encode($websitename), // Encode domain
        'platform'       => 'ClassicPress CMS',
        'is_trial_period'=> 1,
        'name'           => $username,
        'email'          => $email,
        'comapany_name'  => $websitename,
        'package_type'   => '25-pages'
    ];
    // register user domain on scanning & monitoring dashboard
    $ch = curl_init('https://skynetaccessibilityscan.com/api/register-domain-platform');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $arrDetails,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
     ]);
    

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Curl error: ' . curl_error($ch);
    }
    curl_close($ch);

    // Decode API response
    $jsonStart = strpos($response, '{');
    if ($jsonStart !== false) {
        $jsonPart = substr($response, $jsonStart);
        $result = json_decode($jsonPart, true);
        
    } else {
        echo "Invalid response: " . $response;
    } 

   
    $domain_name =  $_SERVER['HTTP_HOST'];
    /**
     * Common function to call cURL POST API
     */
    function callApiPost($url, $postData, $isJson = false) {
        $curl = curl_init();
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ];

        $options[CURLOPT_POSTFIELDS] = $isJson ? json_encode($postData) : $postData;
        if ($isJson) $options[CURLOPT_HTTPHEADER] = ['Content-Type: application/json'];

        curl_setopt_array($curl, $options);
        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response, true);
    }

    // ----------------- Get Scan Detail -----------------
    $row = callApiPost('https://skynetaccessibilityscan.com/api/get-scan-detail', [
        'website' => base64_encode($domain_name)
    ])['data'][0] ?? [];

    $data = [
        'domain' => $row['domain'] ?? '',
        'fav_icon' => $row['fav_icon'] ?? '',
        'url_scan_status' => $row['url_scan_status'] ?? 0,
        'scan_status' => $row['scan_status'] ?? 0,
        'total_selected_pages' => $row['total_selected_pages'] ?? 0,
        'total_last_scan_pages' => $row['total_last_scan_pages'] ?? 0,
        'total_pages' => $row['total_pages'] ?? 0,
        'last_url_scan' => $row['last_url_scan'] ?? 0,
        'total_scan_pages' => $row['total_scan_pages'] ?? 0,
        'last_scan' => $row['last_scan'] ?? null,
        'next_scan_date' => $row['next_scan_date'] ?? null,
        'success_percentage' => $row['success_percentage'] ?? '0',
        'scan_violation_total' => $row['scan_violation_total'] ?? '0',
        'total_violations' => $row['total_violations'] ?? 0,
        'package_name' => $row['name'] ?? '',
        'package_id' => $row['package_id'] ?? '',
        'page_views' => $row['page_views'] ?? '',
        'package_price' => $row['package_price'] ?? '',
        'subscr_interval' => $row['subscr_interval'] ?? '',
        'end_date' => $row['end_date'] ?? '',
        'cancel_date' => $row['cancel_date'] ?? '',
        'website_id' => $row['website_id'] ?? '',
        'paypal_subscr_id' => $row['paypal_subscr_id'] ?? '',
        'is_trial_period' => $row['is_trial_period'] ?? '',
        'dashboard_link' => callApiPost('https://skynetaccessibilityscan.com/api/get-scan-detail', ['website'=>base64_encode($domain_name)])['dashboard_link'] ?? '',
        'total_fail_sum' => $row['total_fail_sum'] ?? '',
        'is_expired' => $row['is_expired'] ?? ''
    ];

    // ----------------- Get Scan Count -----------------
    $result1 = callApiPost('https://skynetaccessibilityscan.com/api/get-scan-count', [
        'website' => base64_encode($domain_name)
    ]);

    $widgetPurchased = $result1['widget_purchased'] ?? false;
    $data['scan_details'] = [
        'with_remediation' => $widgetPurchased ? ($result1['scan_details']['with_remediation'] ?? []) : ($result1['scan_details']['without_remediation'] ?? [])
    ];

    // ----------------- Fetch Packages -----------------
    $decoded = callApiPost('https://skynetaccessibilityscan.com/api/packages-list', [
        'website' => base64_encode($domain_name)
    ], true);

    $activePackageId = $data['package_id'] ?? '';
    $activeInterval  = $data['subscr_interval'] ?? '';
    $websiteId       = (string)($data['website_id'] ?? '');
    $today           = new \DateTime('now', new \DateTimeZone('UTC'));

    $packageData = $decoded['current_active_package'][$websiteId] ?? $decoded['expired_package_detail'][$websiteId] ?? [];
    $data['final_price'] = $packageData['final_price'] ?? 0;
    $activePackageId = $packageData['package_id'] ?? $activePackageId;
    $activeInterval = $packageData['subscr_interval'] ?? $activeInterval;

    // Generate violation link once
    $violationLinkData = callApiPost('https://skynetaccessibilityscan.com/api/generate-plan-action-link', [
        'website_id' => $websiteId,
        'current_package_id' => $activePackageId,
        'action' => 'violation'
    ]);
    $data['violation_link'] = $violationLinkData['action_link'] ?? '#';

    $data['plans'] = [];
    foreach ($decoded['Data'] as $plan) {
        if (!isset($plan['platforms']) || strtolower($plan['platforms']) !== 'scanner') continue;

        $planId = $plan['id'] ?? null;
        if (!$planId) continue;

        $action = 'upgrade';
        if ($planId == $activePackageId) {
            $plan['interval'] = $activeInterval;
            $endDateStr = $data['end_date'] ?? '';
            if ($endDateStr) {
                $endDate = new \DateTime($endDateStr, new \DateTimeZone('UTC'));
                $action = ($today <= $endDate) ? 'cancel' : 'upgrade';
            } else {
                $action = 'cancel';
            }
        }

        $plan['action'] = $action;
        $data['plans'][] = $plan;
    }

    $data['activePackageId'] = $activePackageId;
    $data['websiteId'] = $websiteId;

?>

<meta name="description" content="" />
<link rel="mask-icon" href="" />
<meta name="Generator" content="Drupal 9 (https://www.drupal.org)" />
<meta name="MobileOptimized" content="width" />
<meta name="HandheldFriendly" content="true" />
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
<meta http-equiv="x-ua-compatible" content="ie=edge" />
<link rel="icon" href="https://www.skynettechnologies.com/sites/default/files/favicon_0.webp" type="image/png" />
<link href="https://sanity.skynettechnologies.us/assets/css/scanning-and-monitoring-app.css" rel="stylesheet">
<style>
    ul {
    list-style: inside;
}
ol, ul {
    padding: revert;
}
body
{
        line-height: initial !important;
}
.card {
   
    max-width: 1280px !important;
}
</style>
</head>

<body class="layout-no-sidebars has-featured-top page-node-1860 path-node node--type-page scrolled scrolldown">

<!-- Section 1-->

<div id="section1">

<div class="dialog-off-canvas-main-canvas" data-off-canvas-main-canvas>
<div id="page-wrapper">
<div id="page">
<div id="main-wrapper" class="layout-main-wrapper clearfix">
<div id="main" class="container">
<div class="row row-offcanvas row-offcanvas-left clearfix">
<main class="main-content col" id="content" r ole="main">
<section class="section">
<div id="main-content" tabindex="-1"></div>
<div id="block-skynettechnologies-content" class="block block-system block-system-main-block">
    <div class="content">
        <article data-history-node-id="529" class="node node--type-page node--view-mode-full clearfix">
            <div class="node__content clearfix ">
                <div class="scanning-monitoring-app">
                    <div class="scans">

                        <p class="title">My Scans</p>

                        <!-- Status Section -->
                        <section class="status" style="background-image: url('https://sanity.skynettechnologies.us/assets/images/sitemap-bg.png');background-repeat: no-repeat;background-position: center;background-size: cover;">
                            <div class="page-background"></div>
                          
                            <div class="status-card">
                                <span class="status-title">Scan Score</span>
                                <?php 
                                    if (!empty($data['is_expired']) && $data['is_expired'] == 1): ?>
                                        <span class="status-value status-inactive">N/A</span>
                                    <?php else: ?>
                                        <?php if (($data['scan_violation_total'] ?? 0) == 0): ?>
                                            <span class="status-value status-inactive">N/A</span>
                                        <?php else: ?>
                                            <span class="status-value status-progress" style="cursor:pointer;" onclick="showDetails()">
                                                <?= $data['success_percentage'] ?? 0; ?>%
                                                <div class="progress-bar">
                                                    <div class="progress-fill" style="width: <?= $data['success_percentage'] ?? 0; ?>%;"></div>
                                                </div>
                                                <div class="violations">
                                                    Violations: <span class="status-value" style="font-size: 15px;"><?= $data['total_fail_sum'] ?? 0; ?></span>
                                                </div>
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                            </div>
                            <div class="status-card">
                                <span class="status-title">Last Scanned</span>
                                 <?php if (($data['url_scan_status'] ?? 0) < 2): ?>
                                  <span class="status-value status-inactive">
                                      <img src="https://sanity.skynettechnologies.us/assets/images/not-shared.svg" 
                                          alt="" 
                                          title="Not Started">
                                      Not Started
                                  </span>

                                 <?php elseif (($data['scan_status'] ?? 0) == 0): ?>
                                <span class="status-value status-inactive">
                                    <img src="https://sanity.skynettechnologies.us/assets/images/not-shared.svg" 
                                        alt="" 
                                        title="Not Started">
                                    Not Started
                                </span>

                                  <?php elseif (($data['scan_status'] ?? 0) == 1 || ($data['scan_status'] ?? 0) == 2): ?>
                                  <span class="status-value status-inactive">
                                      <img src="https://sanity.skynettechnologies.us/assets/images/not-shared.svg" 
                                          alt="" 
                                          title="Scanning in process">
                                      Scanning<br>
                                      <?php echo $data['total_scan_pages'] ?? 0; ?>/<?php echo $data['total_selected_pages'] ?? 0; ?>
                                  </span>

                                <?php elseif (($data['scan_status'] ?? 0) == 3): ?>
                                    <span class="status-value status-active">
                                        <?php echo $data['total_scan_pages'] ?? 0; ?> Pages<br>
                                        <?php 
                                            if (!empty($data['last_scan'])) {
                                                echo date("F jS Y", strtotime($data['last_scan']));
                                            }
                                        ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </section>

                        <hr class="divider">

                       <section class="plan" style="background-image: url('https://sanity.skynettechnologies.us/assets/images/sitemap-bg.png');background-repeat: no-repeat;background-position: center;background-size: cover;">
                            <div class="page-background"></div>
                            <div class="plan-info">
                                <div class="plans-left" style="margin-bottom: 5px;">
      
                                    <span class="plan-type free">
                                        <div class="icon-circle">
                                            <img src="https://sanity.skynettechnologies.us/assets/images/round.svg" alt="" height="20" width="20">
                                        </div>
                                       <?php
                                        $is_expired = isset($data['end_date']) && (date('Y-m-d', strtotime($data['end_date'])) < date('Y-m-d'));
                                        ?>
                                        <span>
                                            <?php if ($data['is_expired']): ?>
                                                <span style="color: #9F0000; font-weight: 700;">Your Plan has Expired</span>
                                            <?php else: ?>
                                                <?php if (!empty($data['is_trial_period']) && $data['is_trial_period'] == 1): ?>
                                                    Free Plan
                                                <?php else: ?>
                                                    <?= htmlspecialchars($data['package_name']) ?> Plan
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </span>

                                        <span class="plan-desc">
                                            <ul>
                                                <?php if (!$data['is_expired']): ?>
                                                    <li>Scan up to <?= htmlspecialchars($data['page_views']) ?> Pages</li>
                                            </ul>
                                        </span>
                                        <?php 
                                          $today = date('Y-m-d');
                                          $cancel = isset($data['cancel_date']) ? substr($data['cancel_date'], 0, 10) : '';
                                          $isExpired = !empty($data['is_expired']);

                                          // Show “Cancelled Plan” only if cancel date <= today OR expired
                                          $isCancelled = ($cancel && $cancel <= $today) || $isExpired;
                                          ?>

                                          <span class="plan-badge" 
                                                style="color: <?= $isCancelled ? '#940000;' : 'green' ?>;
                                                      background: <?= $isCancelled ? '#ffd1d1' : '#D1FFD3' ?>;">
                                              <?= $isCancelled ? 'Cancelled Plan' : 'Current Plan' ?>
                                          </span>
                                <?php endif; ?>
                                </div>
                                <div class="plans-right" style="margin-left:3rem;">
                                <?php if (!$data['is_expired']): ?>
                                    <span class="plan-renewal">
                                        <?php 
                                            $today = date('Y-m-d');
                                            $cancelDate = !empty($data['cancel_date']) ? substr($data['cancel_date'], 0, 10) : '';
                                            $endDate = !empty($data['end_date']) ? substr($data['end_date'], 0, 10) : '';

                                            $isCancelled = ($cancelDate && $cancelDate <= $today);
                                            $isExpired = !empty($data['is_expired']) && $data['is_expired']; // assuming this is boolean true/false

                                           
                                        if (!empty($endDate)) {
                                            $formattedDate = date("F j, Y", strtotime($endDate));

                                            // Trial period → always expires
                                            if (!empty($data['is_trial_period']) && $data['is_trial_period'] == 1) {
                                                echo '<span style="color:#9F0000;">Expires on:</span> <strong>' . $formattedDate . '</strong>';
                                            } 
                                            // Non-trial
                                            else {
                                                if ($isExpired || $isCancelled) {
                                                    echo '<span style="color:#9F0000;">Expires on:</span> <strong>' . $formattedDate . '</strong>';
                                                } else {
                                                    echo 'Renews on: <strong>' . $formattedDate . '</strong>';
                                                }
                                            }
                                        }
                                        ?>
                                    </span>
                                    <?php else: ?>
                                        <span class="plan-renewal">
                                            <?php 
                                                if (!empty($data['end_date'])) {
                                                    $formattedDate = date("F j, Y", strtotime($data['end_date']));
                                                    echo 'Expired on: <strong>' . $formattedDate . '</strong>';
                                                }
                                            ?>
                                        </span>
                                    <?php endif; ?>
									<?php
										$today = date('Y-m-d');
										$cancel = isset($data['cancel_date']) ? substr($data['cancel_date'], 0, 10) : '';
										$isExpired = !empty($data['is_expired']);
										$isTrial = !empty($data['is_trial_period']) && $data['is_trial_period'] == 1;

										// Button logic
										if($isExpired)
										{
											$buttonText = 'Renew Plan';
											$buttonStyle = 'background-color:#420083;color:#fff;';
										} 
										elseif ($isTrial) {
											$buttonText = 'Activate Now';
											$buttonStyle = 'background-color:#420083;color:#fff;';
										}
										elseif (!empty($cancel) && $cancel <= $today) {
											$buttonText = 'Renew Plan';
											$buttonStyle = 'background-color:#420083;color:#fff;';
										} else {
											$buttonText = 'Cancel Subscription';
											$buttonStyle = '';
										}
									?>
									<button class="cancel-btn" style="<?= $buttonStyle ?>" onclick="window.open('<?= htmlspecialchars($data['dashboard_link']) ?>','_blank')"> <?= $buttonText ?></button>
                                </div>
                            </div>
                       </section>
                        <!-- Plan Section -->
                        <section class="pricing" style="background-image: url('https://sanity.skynettechnologies.us/assets/images/sitemap-bg.png');background-repeat: no-repeat;background-position: center;background-size: cover;">
                            <div class="page-background"></div>
                            <div class="billing-toggle">
                                <span class="label active" id="monthly-label">Pay
                                    Monthly</span>
                                <label class="switch">
                                    <input type="checkbox" id="billing-toggle">
                                    <span class="slider"></span>
                                </label>
                                <span class="label" id="annual-label">Pay
                                    Annually</span>

                                <span class="save">Save
                                    20%</span>
                            </div>
                            <!-- Monthly Plans -->
                            <div id="monthlyclass" class="monthlyclass">                                                                                                     
                                <div class="pricing-tiers">
                                    <?php if (!empty($data['plans'])): ?>
                                        <?php foreach ($data['plans'] as $index => $plan): ?>
                                            <div class="tier"
                                                data-plan-id="<?php echo $plan['id']; ?>"
                                                data-annual-price="<?php echo $plan['price']; ?>"
                                                data-monthly-price="<?php echo $plan['monthly_price']; ?>">

                                                <div class="pricing-top">
                                                    <div class="pricing-header">
                                                        <div class="icon-circle">
                                                            <?php 
                                                                $icons = ['diamond.svg', 'pentagon.svg', 'hexagon.svg', 'hexagon.svg'];
                                                                $icon = $icons[$index] ?? 'default.svg'; // fallback if $index out of range
                                                            ?>
                                                            <img src="https://sanity.skynettechnologies.us/assets/images/<?= $icon ?>" alt="" height="20" width="20">
                                                        </div>
                                                    </div>
                                                    <div class="pricing-info">
                                                        <h3 class="tier-title"><?php echo $plan['name']; ?></h3>
                                                        <p class="tier-pages"><?php echo $plan['page_views']; ?> Pages</p>
                                                    </div>
                                                </div>

                                                <hr class="pricing-divider" style="width:auto;">

                                                <div class="pricing-body">
                                                    <p class="old-price">$<?php echo $plan['strick_monthly_price']; ?></p>
                                                    <p class="new-price">$<?php echo $plan['monthly_price']; ?><span class="per-year">/Monthly</span></p>
                                                </div>

                                                <?php                                      // Check if plan expired
                                                $is_expired = $data['end_date'] && (date('Y-m-d', strtotime($data['end_date'])) < date('Y-m-d'));
                                                ?>
                                            
                                                <button class="upgrade-btn<?= (!$is_expired && $data['package_id'] == $plan['id'] && $plan['interval'] == 'M') ? ' cancel-btnn' : '' ?>" data-action="<?= $is_expired ? 'upgrade' : (($data['package_id'] == $plan['id'] && $plan['interval'] == 'M') ? 'cancel' : 'upgrade') ?>" onclick="handleUpgradeClick('<?= $plan['id'] ?>', this.dataset.action, 'M')"><?= $is_expired ? 'Upgrade' : (($data['package_id'] == $plan['id'] && $plan['interval'] == 'M') ? 'Cancel' : 'Upgrade') ?></button>

                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p>No plans available.</p>
                                    <?php endif; ?>
                                </div>

                            </div>
                            <!-- Annual Plans -->
                            <div id="annualclass" class="annualclass">                                                                                                   
                            <div class="pricing-tiers">
                                    <?php if (!empty($data['plans'])): ?>
                                        <?php foreach ($data['plans'] as $index => $plan): ?>
                                            <div class="tier"
                                                data-plan-id="<?php echo $plan['id']; ?>"
                                                data-annual-price="<?php echo $plan['strick_price']; ?>"
                                                data-monthly-price="<?php echo $plan['strick_monthly_price']; ?>">

                                                <div class="pricing-top">
                                                <div class="pricing-header">
                                                <div class="icon-circle">
                                                    <?php 
                                                    $icons = ['diamond.svg', 'pentagon.svg', 'hexagon.svg', 'hexagon.svg'];
                                                    $icon = $icons[$index] ?? 'default.svg'; // fallback if $index out of range
                                                    ?>
                                                    <img src="https://sanity.skynettechnologies.us/assets/images/<?= $icon ?>" alt="" height="20" width="20">
                                            </div>
                                                </div>
                                                    <div class="pricing-info">
                                                        <h3 class="tier-title"><?php echo $plan['name']; ?></h3>
                                                        <p class="tier-pages"><?php echo $plan['page_views']; ?> Pages</p>
                                                    </div>
                                                </div>

                                                <hr class="pricing-divider" style="width:auto;">

                                                <div class="pricing-body">
                                                    <p class="old-price">$<?php echo $plan['strick_price']; ?></p>
                                                    <p class="new-price">$<?php echo $plan['price']; ?><span class="per-year">/Year</span></p>
                                                </div>

                                                <?php
                                                $is_expired = $data['end_date'] && (date('Y-m-d', strtotime($data['end_date'])) < date('Y-m-d'));
                                            
                                                ?>
                                                <button class="upgrade-btn<?= (!$is_expired && $data['package_id'] == $plan['id'] && $plan['interval'] == 'Y') ? ' cancel-btnn' : '' ?>" data-action="<?= $is_expired ? 'upgrade' : (($data['package_id'] == $plan['id'] && $plan['interval'] == 'Y') ? 'cancel' : 'upgrade') ?>" onclick="handleUpgradeClick('<?= $plan['id'] ?>', this.dataset.action, 'Y')"><?= $is_expired ? 'Upgrade' : (($data['package_id'] == $plan['id'] && $plan['interval'] == 'Y') ? 'Cancel' : 'Upgrade') ?></button>

                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p>No plans available.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <p class="pricing-contact">
                                Are you looking for a custom plan or Enterprise
                                plan? Contact us
                                <a href="mailto:hello@skynettechnologies.com">hello@skynettechnologies.com</a>
                            </p>
                        </section>

                        <hr class="divider">

                        <!-- Help Section -->
                        <section class="help">
                            <p class="help-text">
                                <strong>Facing any issues with SkynetAccessibility Scanner?</strong>
                                Report a problem, we will get back to you very soon!
                            </p>
                            <a href="https://www.skynettechnologies.com/report-accessibility-problem" class="help-btn">Report a problem</a>
                        </section>
                    </div>
                </div>
            </div>

        </article>
    </div>
</div>
</section>
</main>
</div>
</div>
</div>
</div>
</div>
</div>
</div>

<!-- End Section 1-->

<!-- Section 2-->
<!-- Violation Report data -->
<div id="section2" style="display:none;">
<div class="dialog-off-canvas-main-canvas" data-off-canvas-main-canvas>
<div id="page-wrapper">
<div id="page">
<div id="main-wrapper" class="layout-main-wrapper clearfix">
<div id="main" class="container">
<div class="row row-offcanvas row-offcanvas-left clearfix">
    <main class="main-content col" id="content" r ole="main">
        <section class="section">
            <div id="main-content" tabindex="-1"></div>
            <div id="block-skynettechnologies-content"
                class="block block-system block-system-main-block">
                <div class="content">
                    <article data-history-node-id="529"
                        class="node node--type-page node--view-mode-full clearfix">
                        <div class="node__content clearfix ">
                            <div class="scanning-monitoring-app">
                                <div class="accessibility-report">
                                    <div class="report-date">
                                        <label for="report-date">Report Date:</label>
                                        <select id="report-date">
                                           <option selected>
                                                <?php 
                                                    if (!empty($data['last_scan'])) {
                                                        echo date("jS F, Y", strtotime($data['last_scan']));
                                                    }
                                                ?>
                                            </option>
                                        </select>
                                    </div>

                                    <section class="top-section">
                                        <div class="card score-card">
                                            <h3>Accessibility Score</h3>
                                            <div class="accessibility-score">
                                                <div class="score-value">
                                                    <?php echo isset($data['success_percentage']) ? $data['success_percentage'] : 0; ?>%
                                                </div>
                                                <?php 
                                                    $percentage = isset($data['success_percentage']) ? $data['success_percentage'] : 0;
                                                    $statusClass = '';
                                                    $statusText  = '';

                                                    if ($percentage >= 0 && $percentage < 50) {
                                                        $statusClass = 'not-compliant';
                                                        $statusText  = 'Not Compliant';
                                                    } elseif ($percentage >= 50 && $percentage < 85) {
                                                        $statusClass = 'semi-compliant';
                                                        $statusText  = 'Semi Compliant';
                                                    } elseif ($percentage >= 85) {
                                                        $statusClass = 'compliant';
                                                        $statusText  = 'Compliant';
                                                    }
                                                ?>

                                            <span class="status-text <?php echo $statusClass; ?>">
                                                <?php echo $statusText; ?>
                                            </span>
                                            </div>
                                            <div class="progress-bar">
                                                <div class="progress-fill" style="width: <?php echo $percentage; ?>%;"></div>
                                            </div>
                                            <p class="note">
                                                Automated Accessibility score has limitations.
                                                We recommend Manual Accessibility Audit.
                                            </p>
                                        </div>

                                       <!-- Web Pages Scanned -->
                                        <div class="card pages-card">
                                            <h3>Web Pages Scanned</h3>
                                            <div class="pages-value">
                                                <?php echo isset($data['total_scan_pages']) ? $data['total_scan_pages'] : 0; ?>
                                            </div>

                                            <?php
                                                $totalScanPages = isset($data['total_scan_pages']) ? $data['total_scan_pages'] : 0;
                                                $totalPages     = isset($data['total_pages']) ? $data['total_pages'] : 0;
                                                $progressWidth  = ($totalPages > 0) ? ($totalScanPages / $totalPages * 100) : 0;
                                            ?>

                                            <div class="progress-bar">
                                                <div class="progress-fill" style="width: <?php echo $progressWidth; ?>%;"></div>
                                            </div>

                                            <p class="note">
                                                <?php echo $totalScanPages; ?> pages scanned out of <?php echo $totalPages; ?>
                                            </p>
                                        </div>

                                    </section>

                                    <!-- WCAG Section -->
                                    <section class="wcag-section">
                                        <div class="wcag-header">
                                            <h3>WCAG 2.1/2.2</h3>
                                            <button onclick="window.open('<?php echo isset($data['violation_link']) ? $data['violation_link'] : '#'; ?>', '_blank')" class="view-btn"> View all Violations <svg
                                            xmlns="http://www.w3.org/2000/svg" width="6"
                                            height="10" viewBox="0 0 6 10" fill="none">
                                            <path
                                            d="M6 5.00002C6 5.17924 5.92797 5.35843 5.78422 5.49507L1.25832 9.79486C0.970413 10.0684 0.503627 10.0684 0.21584 9.79486C-0.0719468 9.52145 -0.0719468 9.07807 0.21584 8.80452L4.22061 5.00002L0.21598 1.19549C-0.0718073 0.921968 -0.0718073 0.478632 0.21598 0.205242C0.503767 -0.0684128 0.970553 -0.0684128 1.25846 0.205242L5.78436 4.50496C5.92814 4.64166 6 4.82086 6 5.00002Z"
                                            fill="white" />
                                            </svg>
                                          </button>
                                        </div>

                                        <!-- Checks Grid -->
                                        <div class="checks-grid">
                                            <div class="check-card failed">
                                                <span class="check-value">
                                                    <?php echo isset($data['scan_details']['with_remediation']['total_fail']) ? $data['scan_details']['with_remediation']['total_fail'] : 0; ?>
                                                </span>
                                                <span class="check-label">Failed Checks</span>
                                            </div>

                                            <div class="check-card passed">
                                                <span class="check-value">
                                                    <?php echo isset($data['scan_details']['with_remediation']['total_success']) ? $data['scan_details']['with_remediation']['total_success'] : 0; ?>
                                                </span>
                                                <span class="check-label">Passed Checks</span>
                                            </div>

                                            <div class="check-card na">
                                                <span class="check-value">
                                                    <?php echo isset($data['scan_details']['with_remediation']['severity_counts']['Not_Applicable']) ? $data['scan_details']['with_remediation']['severity_counts']['Not_Applicable'] : 0; ?>
                                                </span>
                                                <span class="check-label">N/A Checks</span>
                                            </div>
                                        </div>


                                        <hr class="divider" style="width:auto;">

                                        <!-- Violations Grid -->
                                        <div class="violations-grid">
                                            <div class="violation-card">
                                                <span class="violation-title">Level A</span>
                                                <span class="violation-count">
                                                    <span>
                                                        <?php echo isset($data['scan_details']['with_remediation']['criteria_counts']['A']) ? $data['scan_details']['with_remediation']['criteria_counts']['A'] : 0; ?>
                                                    </span> violations
                                                </span>
                                            </div>

                                            <div class="violation-card">
                                                <span class="violation-title">Level AA</span>
                                                <span class="violation-count">
                                                    <span>
                                                        <?php echo isset($data['scan_details']['with_remediation']['criteria_counts']['AA']) ? $data['scan_details']['with_remediation']['criteria_counts']['AA'] : 0; ?>
                                                    </span> violations
                                                </span>
                                            </div>

                                            <div class="violation-card">
                                                <span class="violation-title">Level AAA</span>
                                                <span class="violation-count">
                                                    <span>
                                                        <?php echo isset($data['scan_details']['with_remediation']['criteria_counts']['AAA']) ? $data['scan_details']['with_remediation']['criteria_counts']['AAA'] : 0; ?>
                                                    </span> violations
                                                </span>
                                            </div>
                                        </div>

                                    </section>
                                    <br>
                                     <button class="back-btn" onclick="goBack()">Back</button>
                                </div>
                          
                            </div>
                        </div>

                    </article>
                </div>
            </div>
        </section>
    </main>
</div>
</div>
</div>
</div>
</div>
</div>
</div>

<!-- End Section 2-->

</body>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const target = document.body;

        const observer = new MutationObserver(() => {
            document.querySelectorAll('input#billing-toggle + .checkbox')
                .forEach(el => el.remove());
        });

        observer.observe(target, { childList: true, subtree: true });
    });

    function handleUpgradeClick(planId, actionType, interval) {
        const websiteId = "<?= $data['website_id'] ?? '' ?>";
        const paypalSubscrId = "<?= $data['paypal_subscr_id'] ?? '' ?>"; // get PayPal ID from PHP
    

        // Force action to 'upgrade' if PayPal subscription ID is null or empty
        if (!paypalSubscrId || paypalSubscrId === 'null') {
            actionType = 'upgrade';
        }

        // Prepare request payload
        const payload = {
            website_id: websiteId,
            current_package_id: "<?= $data['package_id'] ?? '' ?>",
            action: actionType
        };

        // Only include package_id and interval for upgrade
        if (actionType === 'upgrade') {
            payload.package_id = planId;
            payload.interval = interval; // 'M' or 'Y'
        }

        const formBody = new URLSearchParams(payload).toString();
        const newWindow = window.open('', '_blank'); // open blank tab immediately

        fetch('https://skynetaccessibilityscan.com/api/generate-plan-action-link', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formBody
        })
        .then(res => res.json())
        .then(data => {
            console.log("API Response:", data);
            const redirectUrl = data.action_link || data.url;
            if (redirectUrl) {
                newWindow.location.href = redirectUrl; // update opened tab
            } else {
                newWindow.close();
                alert("No link returned from API");
            }
        })
        .catch(err => console.error("API Error:", err));
    }
    document.addEventListener('DOMContentLoaded', function () {
        const activeSubscrInterval = "<?= $data['subscr_interval'] ?? '' ?>"; // 'M' = Monthly, 'Y' = Annual
        const endDateStr = "<?= $data['end_date'] ?? '' ?>";
        const paypalSubscrId = "<?= $data['paypal_subscr_id'] ?? '' ?>";

        //  Fetch cancel date if set, even if null
        const cancelDateStr = "<?= isset($data['cancel_date']) ? substr($data['cancel_date'],0,10) : '' ?>";
        const todayStr = new Date().toISOString().split('T')[0]; // YYYY-MM-DD

        const toggle = document.getElementById("billing-toggle");
        const monthlyLabel = document.getElementById("monthly-label");
        const annualLabel = document.getElementById("annual-label");
        const monthlyclass = document.getElementById("monthlyclass");
        const annualclass = document.getElementById("annualclass");

        //  Expired check
        const isExpired = endDateStr && new Date(endDateStr) < new Date();

        //  Cancel check — only if cancelDateStr is not empty
        const isCancelToday = cancelDateStr && cancelDateStr <= todayStr;

        //  Update Upgrade/Cancel buttons
        function updateButtons(container) {
            container.querySelectorAll('.upgrade-btn').forEach(btn => {
                let planAction = btn.dataset.action;

                // If PayPal subscription ID is null or empty → force upgrade
                if (!paypalSubscrId || paypalSubscrId === 'null') {
                    planAction = 'upgrade';
                }

                // If plan expired OR cancel date is today/past → Upgrade
                if (isExpired || isCancelToday) {
                    btn.textContent = 'Upgrade';
                    btn.dataset.action = 'upgrade';
                    btn.classList.remove('cancel-btnn');
                } else {
                    if (planAction === 'cancel') {
                        btn.textContent = 'Cancel';
                        btn.dataset.action = 'cancel';
                        btn.classList.add('cancel-btnn');
                    } else {
                        btn.textContent = 'Upgrade';
                        btn.dataset.action = 'upgrade';
                        btn.classList.remove('cancel-btnn');
                    }
                }
            });
        }

        updateButtons(monthlyclass);
        updateButtons(annualclass);

        //  Toggle Monthly / Annual Plans
        function showMonthly() {
            toggle.checked = false;
            monthlyLabel.classList.add("active");
            annualLabel.classList.remove("active");
            monthlyclass.style.display = "block";
            annualclass.style.display = "none";
        }

        function showAnnual() {
            toggle.checked = true;
            monthlyLabel.classList.remove("active");
            annualLabel.classList.add("active");
            monthlyclass.style.display = "none";
            annualclass.style.display = "block";
        }

        if (activeSubscrInterval === 'Y') {
            showAnnual();
        } else {
            showMonthly();
        }

        toggle.addEventListener("change", () => {
            if (toggle.checked) showAnnual();
            else showMonthly();
        });
    });
    // Hide and show violation report section function
    function showDetails() {
        document.getElementById("section1").style.display = "none";
        document.getElementById("section2").style.display = "block";
    }

    function goBack() {
        document.getElementById("section2").style.display = "none";
        document.getElementById("section1").style.display = "block";
    }
</script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"
integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <?php
}