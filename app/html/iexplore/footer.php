<!-- Modern Footer -->
<footer class="bg-light border-top py-4 mt-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="d-flex align-items-center">
                    <!-- <img src="logo.svg" alt="Logo" style="height: 40px;" class="me-3"> -->
                    <span class="text-muted">© 2025 Rina Shiksha Sahayak Foundation. All rights reserved.</span>
                </div>
            </div>
            <div class="col-md-6 text-end">
                <a href="https://www.instagram.com/rssi.in/" class="text-muted  me-3" target="_blank"><i class="bi bi-instagram"></i></a>
                <a href="https://www.linkedin.com/company/rssingo/" class="text-muted  me-3" target="_blank"><i class="bi bi-linkedin"></i></a>
                <a href="https://x.com/RssiNgo" class="text-muted me-3" target="_blank"><i class="bi bi-twitter-x" target="_blank"></i></a>
                <a href="https://www.facebook.com/rssi.in" class="text-muted me-3" target="_blank"><i class="bi bi-facebook"></i></a>
            </div>
        </div>
    </div>
</footer>
<!-- Back to Top Arrow -->
<button id="back-to-top" class="back-to-top">
    <i class="bi bi-arrow-up"></i> <!-- Bootstrap Icons arrow-up -->
</button>
<script>
    // Back to Top Arrow Logic
    document.addEventListener("DOMContentLoaded", function() {
        const backToTopButton = document.getElementById("back-to-top");

        // Show/hide the arrow based on scroll position
        window.addEventListener("scroll", function() {
            if (window.scrollY > 300) { // Show after scrolling 300px
                backToTopButton.style.display = "block";
            } else {
                backToTopButton.style.display = "none";
            }
        });

        // Scroll to top when the arrow is clicked
        backToTopButton.addEventListener("click", function() {
            window.scrollTo({
                top: 0,
                behavior: "smooth" // Smooth scroll
            });
        });
    });
</script>