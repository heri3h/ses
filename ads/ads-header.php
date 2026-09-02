<script async src="https://securepubads.g.doubleclick.net/tag/js/gpt.js"></script>

<script>
    window.googletag = window.googletag || {cmd: []};
    var slotSide, slotHeader, slotFeed, slotInt, slotSticky;

    googletag.cmd.push(function() {
        
        // 0. IDENTITAS SITUS (Update: Deteksi URL & Domain)
        var currentUrl = window.location.href;
        var currentDomain = window.location.hostname.replace('www.', '');

        // Set Page URL agar GAM mengenali domain & path secara presisi (Penting untuk ads.txt)
        googletag.pubads().set('page_url', currentUrl);
        
        // Targeting tambahan untuk laporan di Dashboard GAM
        googletag.pubads().setTargeting('domain', currentDomain);
        googletag.pubads().setTargeting('site_name', 'arcadefun');

        // 1. KONFIGURASI LAZY LOAD
        googletag.pubads().enableLazyLoad({
            fetchMarginPercent: 200,
            renderMarginPercent: 100,
            mobileScalingPercent: 2.0
        });

        // 2. SIZE MAPPING
        var mappingFlexible = googletag.sizeMapping()
            .addSize([1024, 0], [[970, 250], [970, 90], [728, 90]])
            .addSize([768, 0], [[728, 90]])
            .addSize([0, 0], [[300, 250], [336, 280], [320, 50]])
            .build();

        var mappingSide = googletag.sizeMapping()
            .addSize([1024, 0], [[300, 600], [300, 250], [160, 600]])
            .addSize([0, 0], [[300, 250], [250, 250]])
            .build();

        var mappingSide2 = googletag.sizeMapping()
            .addSize([1024, 0], [[160, 600]])
            .addSize([0, 0], [[300, 250], [250, 250]])
            .build();

        // 3. DEFINISI SLOT IKLAN
        slotHeader = googletag.defineSlot('/22806125615/gm-header', [[728, 90], [970, 250], 'fluid'], 'div-gpt-ad-gm-header');
        if (slotHeader) {
            slotHeader.defineSizeMapping(mappingFlexible).addService(googletag.pubads());
        }
        
        slotFeed = googletag.defineSlot('/22806125615/gm-feed', [[300, 250], [970, 90],'fluid'], 'div-gpt-ad-gm-feed');
        if (slotFeed) {
            slotFeed.defineSizeMapping(mappingFlexible).addService(googletag.pubads());
        }
        
        slotSide = googletag.defineSlot('/22806125615/gm-side', [[300, 600], [300, 250]], 'div-gpt-ad-gm-side');
        if (slotSide) {
            slotSide.defineSizeMapping(mappingSide).addService(googletag.pubads());
        }

        slotSide = googletag.defineSlot('/22806125615/gm-side-2', [[160, 600]], 'div-gpt-ad-gm-side-2');
        if (slotSide) {
            slotSide.defineSizeMapping(mappingSide2).addService(googletag.pubads());
        }

        // 4. OUT-OF-PAGE SLOT
        slotInt = googletag.defineOutOfPageSlot('/22806125615/gm-int', googletag.enums.OutOfPageFormat.INTERSTITIAL);
        if (slotInt) { slotInt.addService(googletag.pubads()); }

        slotSticky = googletag.defineOutOfPageSlot('/22806125615/gm-sticky', googletag.enums.OutOfPageFormat.BOTTOM_ANCHOR);
        if (slotSticky) { slotSticky.addService(googletag.pubads()); }

        // 5. SETTING GLOBAL PERFORMA
        googletag.pubads().enableSingleRequest();
        googletag.pubads().collapseEmptyDivs();
        googletag.enableServices();
    });

    // 6. LOGIKA AUTO REFRESH SIDEBAR
    setInterval(function() {
        if (slotSide && !document.hidden) {
            googletag.cmd.push(function() {
                googletag.pubads().refresh([slotSide]);
                console.log("GAM: Sidebar Refreshed for domain " + window.location.hostname);
            });
        }
    }, 30000); 
</script>