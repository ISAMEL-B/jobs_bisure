<!-- =========================================================
FOOTER
========================================================= -->
<footer style="background: linear-gradient(135deg, #2D3436 0%, #1a1a2e 100%); color: #DFE6E9; padding: 40px 20px 20px; margin-top: 40px;">
    <div class="container" style="max-width: 1400px; margin: 0 auto;">
        
        <div style="display: flex; flex-wrap: wrap; justify-content: space-between; gap: 30px; margin-bottom: 30px;">

            <!-- Footer Logo & About -->
            <div style="flex: 1; min-width: 200px;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                    <i class="bi bi-briefcase-fill" style="font-size: 28px; color: #6C5CE7;"></i>

                    <span style="
                        font-size: 20px;
                        font-weight: 700;
                        background: linear-gradient(135deg, #6C5CE7, #00CEC9);
                        -webkit-background-clip: text;
                        -webkit-text-fill-color: transparent;
                    ">
                        Job Finder
                    </span>
                </div>

                <p style="
                    font-size: 13px;
                    line-height: 1.6;
                    color: #B2BEC3;
                ">
                    Your trusted source for the latest job opportunities.
                    Find your dream career today.
                </p>
            </div>

            <!-- Quick Links -->
            <div style="flex: 1; min-width: 150px;">

                <h4 style="
                    color: white;
                    font-size: 16px;
                    margin-bottom: 15px;
                    font-weight: 700;
                ">
                    Quick Link
                </h4>

                <ul style="list-style:none; padding:0; margin:0;">

                    <li style="margin-bottom:8px;">
                        <a href="/jobaggregator/"
                        style="
                        color:#B2BEC3;
                        text-decoration:none;
                        font-size:13px;
                        transition:0.3s;
                        ">
                            <i class="bi bi-chevron-right"
                            style="font-size:10px; margin-right:5px;"></i>
                            BISure
                        </a>
                    </li>

                    <?php if(isset($_SESSION['user_id'])): ?>
                    <li style="margin-bottom:8px;">
                        <a href="/jobaggregator/feed_landing_all"
                        style="
                        color:#B2BEC3;
                        text-decoration:none;
                        font-size:13px;
                        transition:0.3s;
                        ">
                            <i class="bi bi-chevron-right"
                            style="font-size:10px; margin-right:5px;"></i>
                            Public
                        </a>
                    </li>
                    <?php endif; ?>

                </ul>
            </div>

            <!-- Contact -->
            <div style="flex:1; min-width:180px;">

                <h4 style="
                    color:white;
                    font-size:16px;
                    margin-bottom:15px;
                    font-weight:700;
                ">
                    Contact
                </h4>

                <ul style="list-style:none; padding:0; margin:0;">

                    <li style="
                        margin-bottom:10px;
                        font-size:13px;
                        display:flex;
                        align-items:center;
                        gap:8px;
                    ">
                        <i class="bi bi-envelope" style="color:#6C5CE7;"></i>
                        <span style="color:#B2BEC3;">
                            info@bisuredev.com
                        </span>
                    </li>

                    <li style="
                        margin-bottom:10px;
                        font-size:13px;
                        display:flex;
                        align-items:center;
                        gap:8px;
                    ">
                        <i class="bi bi-telephone" style="color:#6C5CE7;"></i>
                        <span style="color:#B2BEC3;">
                            +256 764 920 075
                        </span>
                    </li>

                    <li style="
                        margin-bottom:10px;
                        font-size:13px;
                        display:flex;
                        align-items:center;
                        gap:8px;
                    ">
                        <i class="bi bi-geo-alt" style="color:#6C5CE7;"></i>
                        <span style="color:#B2BEC3;">
                            Mbarara, Uganda
                        </span>
                    </li>

                </ul>
            </div>

            <!-- Social -->
            <div style="flex:1; min-width:150px;">

                <h4 style="
                    color:white;
                    font-size:16px;
                    margin-bottom:15px;
                    font-weight:700;
                ">
                    Follow Us
                </h4>

                <div class="social-links"
                     style="display:flex; gap:12px;">

                    <a href="#"
                       style="
                       background:rgba(255,255,255,0.1);
                       width:36px;
                       height:36px;
                       border-radius:50%;
                       display:flex;
                       align-items:center;
                       justify-content:center;
                       color:white;
                       text-decoration:none;
                       transition:0.3s;
                       ">
                        <i class="bi bi-facebook"></i>
                    </a>

                    <a href="#"
                       style="
                       background:rgba(255,255,255,0.1);
                       width:36px;
                       height:36px;
                       border-radius:50%;
                       display:flex;
                       align-items:center;
                       justify-content:center;
                       color:white;
                       text-decoration:none;
                       transition:0.3s;
                       ">
                        <i class="bi bi-twitter-x"></i>
                    </a>

                    <a href="#"
                       style="
                       background:rgba(255,255,255,0.1);
                       width:36px;
                       height:36px;
                       border-radius:50%;
                       display:flex;
                       align-items:center;
                       justify-content:center;
                       color:white;
                       text-decoration:none;
                       transition:0.3s;
                       ">
                        <i class="bi bi-linkedin"></i>
                    </a>

                    <a href="#"
                       style="
                       background:rgba(255,255,255,0.1);
                       width:36px;
                       height:36px;
                       border-radius:50%;
                       display:flex;
                       align-items:center;
                       justify-content:center;
                       color:white;
                       text-decoration:none;
                       transition:0.3s;
                       ">
                        <i class="bi bi-whatsapp"></i>
                    </a>

                </div>
            </div>

        </div>

        <!-- Copyright -->
        <div style="
            border-top:1px solid rgba(255,255,255,0.1);
            padding-top:20px;
            text-align:center;
            font-size:12px;
            color:#B2BEC3;
        ">

            <p>
                &copy; <?= date('Y') ?> BISure Jobs.
                All rights reserved.
                |
                Developed with
                <i class="bi bi-heart-fill"
                   style="color:#FF7675;"></i>
                for all job seekers
            </p>

        </div>
    </div>
</footer>

<style>
footer a:hover {
    color: #6C5CE7 !important;
    transform: translateX(3px);
}

footer .social-links a:hover {
    transform: translateY(-3px);
    background: #6C5CE7 !important;
}
</style>

</body>
</html>