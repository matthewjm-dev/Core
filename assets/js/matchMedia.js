/*! matchMedia() polyfill - Test a CSS media type/query in JS. Authors & copyright (coffee) 2012: Scott Jehl, Paul Irish, Nicholas Zakas, David Knight. Dual MIT/BSD license */
window.matchMedia || (window.matchMedia = function () {
    var b = (window.styleMedia || window.media);
    if (!b) {
        var c = document.createElement("style"), a = document.getElementsByTagName("script")[0], d = null;
        c.type = "text/css";
        c.id = "matchmediajs-test";
        a.parentNode.insertBefore(c, a);
        d = ("getComputedStyle" in window) && window.getComputedStyle(c, null) || c.currentStyle;
        b = {
            matchMedium: function (e) {
                var f = "@media " + e + "{ #matchmediajs-test { width: 1px; } }";
                if (c.styleSheet) {
                    c.styleSheet.cssText = f
                } else {
                    c.textContent = f
                }
                return d.width === "1px"
            }
        }
    }
    return function (e) {
        return {matches: b.matchMedium(e || "all"), media: e || "all"}
    }
}());

var media_sizes = {xl: 1200, lg: 992, md: 768, sm: 545, xs: 1};

var media_queries = {xl: 0, lg: 0, md: 0, sm: 0, xs: 0};

function refreshMediaQueries() {
    media_queries.xl = window.matchMedia('(min-width:1200px)');
    media_queries.lg = window.matchMedia('(min-width:992px) and (max-width: 1199px)');
    media_queries.md = window.matchMedia('(min-width:768px) and (max-width: 991px)');
    media_queries.sm = window.matchMedia('(min-width:545px) and (max-width: 767px)');
    media_queries.xs = window.matchMedia('(max-width:544px)');
}

function isBreakpoint(size) {
    return media_queries[size].matches;
}

function getBreakpoint(size) {
    return media_sizes[size];
}

function isMobile() {
    if (isBreakpoint('xs') || isBreakpoint('sm')) {
        return true;
    }
    return false;
}

function isTablet() {
    if (isBreakpoint('md')) {
        return true;
    }
    return false;
}

function isLaptop() {
    if (isBreakpoint('lg')) {
        return true;
    }
    return false;
}

function isDesktop() {
    if (isBreakpoint('xl')) {
        return true;
    }
    return false;
}

function isBreakpointUp(size) {
    if (size == 'xs') {
        return true;
    } else if (size == 'sm') {
        if (isBreakpoint('sm') || isBreakpoint('md') || isBreakpoint('lg') || isBreakpoint('xl')) {
            return true;
        }
    } else if (size == 'md') {
        if (isBreakpoint('md') || isBreakpoint('lg') || isBreakpoint('xl')) {
            return true;
        }
    } else if (size == 'lg') {
        if (isBreakpoint('lg') || isBreakpoint('xl')) {
            return true;
        }
    } else if (size == 'xl') {
        if (isBreakpoint('xl')) {
            return true;
        }
    }

    return false;
}

function isBreakpointDown(size) {
    if (size == 'xs') {
        if (isBreakpoint('xs')) {
            return true;
        }
    } else if (size == 'sm') {
        if (isBreakpoint('xs') || isBreakpoint('sm')) {
            return true;
        }
    } else if (size == 'md') {
        if (isBreakpoint('xs') || isBreakpoint('sm') || isBreakpoint('md')) {
            return true;
        }
    } else if (size == 'lg') {
        if (isBreakpoint('xs') || isBreakpoint('sm') || isBreakpoint('md') || isBreakpoint('lg')) {
            return true;
        }
    } else if (size == 'xl') {
        return true;
    }

    return false;
}

refreshMediaQueries();
jQuery(function ($) {
    $(window).on('resize', refreshMediaQueries);
});
