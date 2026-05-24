<?php
// =============================================================
// LOGIN CHECK 
// =============================================================

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define the redirect function
function redirectToSignin() {
    header('Location: security/signin.php');
    exit();
}

// Check if user is logged in
$isLoggedIn = false;

// Common session variable names - check which one you use
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $isLoggedIn = true;
} 

// If not logged in, redirect
if (!$isLoggedIn) {
    redirectToSignin();
}

// Optional: Store the requested page to redirect back after login
$_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];

// =============================================================
// INCLUDE EXTERNAL HEADER & NAVIGATION FROM bars/
// =============================================================
require_once __DIR__ . '/bars/head_nav.php';
?>

<?php require_once __DIR__ . '/feed_backend.php'; ?>

<?php
// The HTML head, header, navigation, and body opening tag 
// are handled by bars/head_nav.php
?>

<style>
    /* Additional page-specific styles that weren't in the external header */
    .page_heading {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        padding: 16px 20px;
        background: var(--gradient-1);
        border-radius: 14px;
        box-shadow: var(--shadow-md);
        font-size: 22px;
        font-weight: 700;
        color: var(--white);
    }

    .totaljobsheading {
        background: rgba(255, 255, 255, 0.2);
        color: var(--white);
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
        backdrop-filter: blur(10px);
    }

    .totaljobsheading span {
        color: var(--white) !important;
        font-weight: 700;
    }

    #js-jobs-wrapper {
        background: var(--white);
        border-radius: 14px;
        margin-bottom: 10px;
        overflow: hidden;
        transition: 0.3s;
        box-shadow: var(--shadow-sm);
        border: 1px solid #E8E8F0;
    }

    #js-jobs-wrapper:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
        border-color: var(--primary);
    }

    .js-toprow {
        display: flex;
        gap: 15px;
        padding: 16px;
        align-items: flex-start;
    }

    .js-image {
        min-width: 70px;
    }

    .js-image img {
        width: 70px;
        height: 70px;
        border-radius: 10px;
        object-fit: contain;
        background: var(--bg-light);
        border: 2px solid #E8E8F0;
        padding: 6px;
        transition: 0.3s;
    }

    .js-data {
        flex: 1;
    }

    .jobtitle {
        font-size: 17px;
        font-weight: 700;
        color: var(--dark);
        text-decoration: none;
        line-height: 1.4;
        transition: 0.3s;
        background: var(--gradient-1);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .jobtitle:hover {
        background: var(--gradient-2);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .js-category-wrp {
        margin-top: 10px;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 8px;
    }

    .js-fields {
        background: var(--bg-light);
        border-radius: 8px;
        padding: 8px 10px;
        font-size: 12px;
        border: 1px solid #E8E8F0;
        transition: 0.3s;
    }

    .js-fields:hover {
        border-color: var(--primary);
        background: #F0EDFF;
    }

    .js-bold {
        font-weight: 700;
        color: var(--primary);
    }

    .js-bottomrow {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        padding: 10px 16px;
        border-top: 1px solid #E8E8F0;
        background: var(--bg-light);
    }

    .js-actions {
        display: flex;
        gap: 8px;
    }

    .js-button {
        border: none;
        background: #F0EDFF;
        color: var(--primary);
        padding: 8px 14px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 12px;
        text-decoration: none;
        transition: 0.3s;
        position: relative;
        overflow: hidden;
    }

    .js-button:hover {
        background: var(--primary);
        color: var(--white);
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    .js-btn-apply {
        background: var(--gradient-1);
        color: var(--white);
        box-shadow: var(--shadow-sm);
    }

    .js-btn-apply:hover {
        background: var(--gradient-1);
        box-shadow: var(--shadow-lg);
        transform: translateY(-2px);
    }

    .pagination-list {
        display: flex;
        justify-content: center;
        gap: 6px;
        flex-wrap: wrap;
        margin-top: 20px;
        padding: 0;
    }

    .pagination-list li {
        list-style: none;
    }

    .pagination-list li a {
        width: 38px;
        height: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: var(--white);
        color: var(--dark);
        font-weight: 600;
        text-decoration: none;
        box-shadow: var(--shadow-sm);
        transition: 0.3s;
        border: 2px solid #E8E8F0;
    }

    .pagination-list li.active a {
        background: var(--gradient-1);
        color: var(--white);
        border-color: transparent;
        box-shadow: var(--shadow-md);
    }

    .pagination-list li a:hover {
        background: var(--primary);
        color: var(--white);
        border-color: var(--primary);
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    #back-top {
        position: fixed;
        bottom: 30px;
        right: 30px;
        background: var(--gradient-1);
        color: var(--white);
        width: 44px;
        height: 44px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        box-shadow: var(--shadow-md);
        transition: 0.3s;
        z-index: 997;
        font-size: 20px;
    }

    #back-top:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-lg);
    }

    #back-top.backHide {
        opacity: 0;
        visibility: hidden;
        transform: translateY(20px);
    }

    @media(max-width: 768px) {
        .js-toprow {
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 14px;
        }

        .js-bottomrow {
            justify-content: center;
        }

        .js-actions {
            flex-direction: column;
            width: 100%;
        }

        .js-button {
            width: 100%;
            text-align: center;
        }

        .page_heading {
            flex-direction: column;
            gap: 10px;
            text-align: center;
            font-size: 18px;
        }

        .js-category-wrp {
            grid-template-columns: 1fr;
        }

        #back-top {
            bottom: 20px;
            right: 20px;
            width: 40px;
            height: 40px;
        }
    }

    #tellafriend {
        display: none;
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 90%;
        max-width: 400px;
        z-index: 9999;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: var(--shadow-lg);
    }

    #tellafriend_headline {
        background: var(--gradient-1);
        color: var(--white);
        padding: 12px 16px;
        font-size: 16px;
        font-weight: 700;
        border-radius: 12px 12px 0 0;
    }

    .closeimg {
        width: 14px;
        float: right;
        cursor: pointer;
        transition: 0.3s;
    }

    .closeimg:hover {
        transform: rotate(90deg);
    }

    #borderfieldwrapper {
        background: var(--white);
        padding: 16px;
        border-radius: 0 0 12px 12px;
    }

    .fieldwrapper {
        margin-bottom: 12px;
    }

    .fieldtitle {
        margin-bottom: 4px;
        font-size: 12px;
        font-weight: 600;
        color: var(--dark);
    }

    .fieldvalue input,
    .fieldvalue textarea {
        width: 100%;
        border: 2px solid #E8E8F0;
        border-radius: 8px;
        padding: 8px 10px;
        font-size: 13px;
        background: var(--bg-light);
        transition: 0.3s;
    }

    .fieldvalue textarea {
        min-height: 70px;
        resize: vertical;
    }

    .fieldvalue input:focus,
    .fieldvalue textarea:focus {
        outline: none;
        border-color: var(--primary);
        background: var(--white);
        box-shadow: 0 0 0 3px rgba(108, 92, 231, 0.1);
    }

    .js_job_tellafreind_button {
        border: none;
        border-radius: 20px;
        padding: 8px 14px;
        font-weight: 600;
        font-size: 12px;
        cursor: pointer;
        transition: 0.3s;
    }

    .js_job_tellafreind_button.save {
        background: var(--gradient-1);
        color: var(--white);
        box-shadow: var(--shadow-sm);
    }

    .js_job_tellafreind_button.save:hover {
        box-shadow: var(--shadow-md);
        transform: translateY(-2px);
    }

    .js_job_tellafreind_button:not(.save) {
        background: #E8E8F0;
        color: var(--dark);
    }

    .js_job_tellafreind_button:not(.save):hover {
        background: #D1D1E0;
    }

    #js_job_black_friend {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.55);
        z-index: 9998;
    }

    #customToast {
        position: fixed;
        top: 20px;
        right: 20px;
        color: #fff;
        padding: 14px 18px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 280px;
        max-width: 380px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        z-index: 999999;
        transform: translateX(120%);
        opacity: 0;
        transition: all 0.4s ease;
        font-family: inherit;
    }

    #customToast.show {
        transform: translateX(0);
        opacity: 1;
    }

    .toast-success {
        background: linear-gradient(135deg, #00B894 0%, #00CEC9 100%);
    }

    .toast-error {
        background: linear-gradient(135deg, #FF7675 0%, #D63031 100%);
    }

    @media(max-width: 576px) {
        #customToast {
            top: 15px;
            left: 15px;
            right: 15px;
            min-width: auto;
            max-width: none;
        }
    }
</style>

<!-- Main Container - Page Specific Content -->
<section id="g-container-main" class="g-wrapper">
    <div class="g-grid">
        <!-- Sidebar Left -->
        <div class="g-block size-23">
        </div>

        <!-- Main Content Area -->
        <div class="g-block size-54">
            <section id="g-mainbar">
                <div class="g-grid">
                    <div class="g-block size-100">
                        <div class="g-system-messages">
                            <div id="system-message-container" aria-live="polite"></div>
                        </div>
                    </div>
                </div>
                
                <div class="g-grid">
                    <div class="g-block size-100 g-flushed">
                        <div class="g-content">
                            <div class="platform-content container">
                                <div class="row">
                                    <div class="col">
                                        <div id="js_jobs_main_wrapper">
                                            <div id="js_job_black_friend" style="display:none;"></div>
                                            
                                            <!-- Tell A Friend Popup -->
                                            <div id="tellafriend" class="tellafriend" style="display:none;">
                                                <form action="index.php" method="POST">
                                                    <div id="tellafriend_headline">Tell A Friend 
                                                        <img class="closeimg" onclick="closetellafriend();" src="//cdn.greatugandajobs.com/components/com_jsjobs/images/popup-close.png" alt="Close">
                                                    </div>
                                                    <div id="borderfieldwrapper">
                                                        <div class="fieldwrapper">
                                                            <div class="fieldtitle">Your Name<font color="red">*</font></div>
                                                            <div class="fieldvalue">
                                                                <input class="inputbox required" type="text" name="sendername" id="sendername">
                                                            </div>
                                                        </div>
                                                        <div class="fieldwrapper">
                                                            <div class="fieldtitle">Your Email<font color="red">*</font></div>
                                                            <div class="fieldvalue">
                                                                <input class="inputbox required" type="text" name="senderemail" id="senderemail">
                                                            </div>
                                                        </div>
                                                        <div class="fieldwrapper">
                                                            <div class="fieldtitle">Job Link<font color="red">*</font></div>
                                                            <div class="fieldvalue">
                                                                <input class="inputbox required" type="text" name="joblink" id="joblink" disabled style="background:#f0f0f0; color:#6C5CE7; font-size:12px;">
                                                            </div>
                                                        </div>
                                                        <div class="fieldwrapper">
                                                            <div class="fieldtitle">Friend Email<font color="red">*</font></div>
                                                            <div class="fieldvalue">
                                                                <input class="inputbox required validate-email" type="text" name="email1" id="email1">
                                                            </div>
                                                        </div>
                                                        <div class="fieldwrapper">
                                                            <div class="fieldtitle">Message<font color="red">*</font></div>
                                                            <div class="fieldvalue">
                                                                <textarea class="inputbox required" name="message" id="message" rows="3" maxlength="250"></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="fieldwrapper fullwidth button">
                                                            <input class="js_job_tellafreind_button save" type="button" onclick="friendValidate();" value="Send To Friends">
                                                            <input class="js_job_tellafreind_button" type="button" onclick="closetellafriend();" value="Close">
                                                        </div>
                                                        <input type="hidden" name="jobid" id="jobid">
                                                    </div>
                                                </form>
                                            </div>

                                            <div id="jsjobs-wrapper">
                                                <div class="page_heading">
                                                    Jobs in Uganda 
                                                    <div class="totaljobsheading">
                                                        Total jobs: <span><?= number_format($totalJobs) ?></span>
                                                    </div>
                                                </div>
                                                <div class="jsjobs-breadcrunbs-wrp js-breadcrunbs">
                                                </div>

                                                <!-- Dynamic Job Listings -->
                                                <?php if(count($jobs) > 0): ?>
                                                    <?php foreach($jobs as $job): ?>
                                                        <div id="js-jobs-wrapper">
                                                            <div class="js-toprow">
                                                                <div class="js-image">
                                                                    <a href="/jobs/company-detail/company-<?= urlencode($job['company_name']) ?>-<?= $job['id'] ?>/nav-31">
                                                                        <img src="//cdn.greatugandajobs.com/jsjobsdata/data/default_logo_company/defaultlogo.png" 
                                                                             title="<?= htmlspecialchars($job['company_name']) ?>" 
                                                                             style="width:80px; height:80px; object-fit:contain;">
                                                                    </a>
                                                                </div>
                                                                <div class="js-data">
                                                                    <div class="js-first-row">
                                                                        <span class="js-col-xs-12 js-col-md-6 js-title js-title-tablet">
                                                                            <span class="js-status js-type">Full-time</span>
                                                                            <?php if((strtotime(date('Y-m-d')) - strtotime($job['posted_date'])) / (60 * 60 * 24) <= 1): ?>
                                                                                <span class="js-status bg-new" style="background:var(--danger); color:var(--white); padding:2px 8px; border-radius:4px;">New</span>
                                                                            <?php endif; ?>
                                                                        </span>
                                                                    </div>
                                                                    <div class="js-first-row">
                                                                        <span class="js-col-xs-12 js-col-md-6 js-title js-title-tablet">
                                                                            <a class="jobtitle" href="<?= htmlspecialchars($job['apply_url']) ?>" target="_blank">
                                                                                <?= htmlspecialchars($job['title']) ?> job at <?= htmlspecialchars($job['company_name']) ?>
                                                                            </a>
                                                                        </span>
                                                                    </div>
                                                                    <div class="js-second-row js-category-wrp">
                                                                        <div class="js-col-xs-12 js-col-md-5 js-fields">
                                                                            <span class="js-bold">Job Category: </span><?= htmlspecialchars($job['category_name'] ?? 'Other') ?> jobs in Uganda
                                                                        </div>
                                                                        <div class="js-col-xs-12 js-col-md-5 js-fields">
                                                                            <span class="js-bold">Posted: </span><?= date('d M Y', strtotime($job['posted_date'])) ?>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="js-bottomrow">
                                                                <div class="js-col-xs-12 js-col-md-8 js-address"></div>
                                                                <div class="js-col-xs-12 js-col-md-4 js-actions">
                                                                    <button type="button" class="js-button" onclick="showtellafriend('<?= $job['id'] ?>','<?= htmlspecialchars($job['apply_url']) ?>');" style="cursor:pointer;">
                                                                        <i class="bi bi-share"></i> Tell A Friend
                                                                    </button>
                                                                    <a class="js-button js-btn-apply" href="<?= htmlspecialchars($job['apply_url']) ?>" target="_blank">
                                                                        <i class="bi bi-eye"></i> View Details & Apply
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <div class="alert alert-info text-center p-5" style="background: var(--white); border-radius: 14px; box-shadow: var(--shadow-sm);">
                                                        <i class="bi bi-info-circle" style="font-size: 24px; color: var(--primary);"></i>
                                                        <p class="mt-2">No jobs found matching your criteria.</p>
                                                    </div>
                                                <?php endif; ?>

                                                <!-- Pagination -->
                                                <?php if($totalPages > 1): ?>
                                                    <ul class="pagination-list">
                                                        <?php if($page > 1): ?>
                                                            <li>
                                                                <a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&location=<?= urlencode($location) ?>&category=<?= urlencode($category) ?>">
                                                                    <i class="bi bi-chevron-left"></i>
                                                                </a>
                                                            </li>
                                                        <?php endif; ?>
                                                        
                                                        <?php for($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
                                                            <li class="<?= $i == $page ? 'active' : '' ?>">
                                                                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&location=<?= urlencode($location) ?>&category=<?= urlencode($category) ?>">
                                                                    <?= $i ?>
                                                                </a>
                                                            </li>
                                                        <?php endfor; ?>
                                                        
                                                        <?php if($page < $totalPages): ?>
                                                            <li>
                                                                <a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&location=<?= urlencode($location) ?>&category=<?= urlencode($category) ?>">
                                                                    <i class="bi bi-chevron-right"></i>
                                                                </a>
                                                            </li>
                                                        <?php endif; ?>
                                                    </ul>
                                                <?php endif; ?>
                                                
                                                <a class="scrolltask" data-scrolltask="getNextJobs" data-offset="1"></a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- Sidebar Right -->
        <div class="g-block size-23">
            <aside id="g-aside">
                <div class="g-grid">
                    <div class="g-block size-100 hidden-phone">
                        <div class="g-content">
                            <div class="platform-content">
                                <div class="floatingmoduleck" id="floatingmoduleck107">
                                    <div class="floatingmoduleck-inner">
                                        <div class="aside jl-panel moduletable sticky">
                                            <div id="mod-custom107" class="mod-custom custom">
                                                <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-5839100731048282" crossorigin="anonymous"></script>
                                                <ins class="adsbygoogle" style="display:block" data-ad-client="ca-pub-5839100731048282" data-ad-slot="4560494900" data-ad-format="auto" data-full-width-responsive="true"></ins>
                                                <script>(adsbygoogle = window.adsbygoogle || []).push({});</script>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</section>

<!-- Back to Top Button -->
<a id="back-top" href="#" class="back-to-top" aria-label="Back to top" title="Back to top">
    <i class="bi bi-arrow-up"></i>
</a>

<!-- JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="feed_tell_friend_modal_js.js"></script>

<script>
    // Back to Top Button
    var backToTop = document.getElementById('back-top');
    if(backToTop) {
        window.onscroll = function() {
            if(document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
                backToTop.classList.remove('backHide');
            } else {
                backToTop.classList.add('backHide');
            }
        };
        backToTop.onclick = function(e) { 
            e.preventDefault(); 
            window.scrollTo({top: 0, behavior: 'smooth'}); 
        };
    }

    // Tell A Friend Modal
    jQuery(document).ready(function($) {
        $("div#js_job_black_friend").click(function() {
            $("div#tellafriend").fadeOut();
            $("div#js_job_black_friend").fadeOut();
        });
    });

    function closetellafriend() {
        jQuery('#tellafriend').slideUp("slow");
        jQuery('#js_job_black_friend').fadeOut();
    }
    
    function showtellafriend(jobid, joburl) {
        jQuery('#js_job_black_friend').fadeIn();
        jQuery('#tellafriend').slideDown("slow");
        document.getElementById('jobid').value = jobid;
        document.getElementById('joblink').value = joburl;
    }
    
    function friendValidate() {
        let sendername  = document.getElementById('sendername').value.trim();
        let senderemail = document.getElementById('senderemail').value.trim();
        let email1      = document.getElementById('email1').value.trim();
        let message     = document.getElementById('message').value.trim();
        let jobid       = document.getElementById('jobid').value.trim();
        let joblink     = document.getElementById('joblink').value.trim();

        if (sendername === '') {
            alert('Your name is required');
            return;
        }
        if (senderemail === '') {
            alert('Your email is required');
            return;
        }
        if (email1 === '') {
            alert('Friend email is required');
            return;
        }
        if (message === '') {
            alert('Message is required');
            return;
        }

        const btn = document.querySelector('.js_job_tellafreind_button.save');
        btn.disabled = true;
        btn.value = 'Sending...';

        $.ajax({
            url: 'email_friend.php',
            type: 'POST',
            dataType: 'json',
            data: {
                sendername: sendername,
                senderemail: senderemail,
                email1: email1,
                message: message,
                jobid: jobid,
                joblink: joblink
            },
            success: function(response) {
                if (response.success) {
                    showToast('Job email sent to your friend successfully!');
                    document.getElementById('sendername').value = '';
                    document.getElementById('senderemail').value = '';
                    document.getElementById('email1').value = '';
                    document.getElementById('message').value = '';
                    document.getElementById('jobid').value = '';
                    document.getElementById('joblink').value = '';
                    closetellafriend();
                } else {
                    showToast(response.message, 'error');
                }
                btn.disabled = false;
                btn.value = 'Send To Friends';
            },
            error: function(xhr, status, error) {
                console.log('AJAX Error:', xhr.responseText);
                alert('AJAX ERROR:\n\nStatus: ' + status + '\nError: ' + error);
                btn.disabled = false;
                btn.value = 'Send To Friends';
            }
        });
    }
    
    function showToast(message, type = 'success') {
        const oldToast = document.getElementById('customToast');
        if (oldToast) {
            oldToast.remove();
        }
        
        const toast = document.createElement('div');
        toast.id = 'customToast';
        
        if (type === 'error') {
            toast.classList.add('toast-error');
            toast.innerHTML = `
                <div class="toast-icon">
                    <i class="bi bi-exclamation-circle-fill"></i>
                </div>
                <div class="toast-message">
                    ${message}
                </div>
            `;
        } else {
            toast.classList.add('toast-success');
            toast.innerHTML = `
                <div class="toast-icon">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div class="toast-message">
                    ${message}
                </div>
            `;
        }
        
        document.body.appendChild(toast);
        setTimeout(() => toast.classList.add('show'), 100);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 400);
        }, 4000);
    }
</script>

</body>
</html>