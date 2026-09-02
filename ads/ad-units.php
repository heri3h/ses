<?php
function get_ad_unit($name) {
    switch ($name) {
        case 'gm-header':
            // Beri min-height agar tidak menutupi iframe game saat loading
            return '<div id="div-gpt-ad-gm-header" style="min-height:90px;margin-bottom:20px; text-align:center;">
                        <script>googletag.cmd.push(function() { googletag.display("div-gpt-ad-gm-header"); });</script>
                    </div>';
        
        case 'gm-side':
            return '<div id="div-gpt-ad-gm-side" style="min-height:250px;">
                        <script>googletag.cmd.push(function() { googletag.display("div-gpt-ad-gm-side"); });</script>
                    </div>';

        case 'gm-global':
            return '<script>
                        googletag.cmd.push(function() { 
                            googletag.display("/22806125615/gm-int"); 
                            googletag.display("/22806125615/gm-sticky"); 
                        });
                    </script>';
        // ... (unit lainnya)
    }
}

function get_device_type() {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Deteksi Mobile
    if (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $userAgent)) {
        return 'mobile';
    }
    
    // Deteksi Tablet
    if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobile))/i', $userAgent)) {
        return 'tablet';
    }
    
    // Jika bukan keduanya, berarti Desktop (Mac/PC)
    return 'desktop';
}