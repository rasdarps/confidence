jQuery.cookie = function(b, j, m) {
    if (typeof j != "undefined") {
        m = m || {},
        j === null && (j = "",
        m.expires = -1);
        var e = "";
        if (m.expires && (typeof m.expires == "number" || m.expires.toUTCString)) {
            var f;
            typeof m.expires == "number" ? (f = new Date,
            f.setTime(f.getTime() + m.expires * 24 * 60 * 60 * 1e3)) : f = m.expires,
            e = "; expires=" + f.toUTCString()
        }
        var l = m.path ? "; path=" + m.path : ""
          , g = m.domain ? "; domain=" + m.domain : ""
          , a = m.secure ? "; secure" : "";
        document.cookie = [b, "=", encodeURIComponent(j), e, l, g, a].join("")
    } else {
        var d = null;
        if (document.cookie && document.cookie != "")
            for (var k = document.cookie.split(";"), h = 0; h < k.length; h++) {
                var c = jQuery.trim(k[h]);
                if (c.substring(0, b.length + 1) == b + "=") {
                    d = decodeURIComponent(c.substring(b.length + 1));
                    break
                }
            }
        return d
    }
}
,
Shopify.Products = (function() {
    var a = {
        howManyToShow: 3,
        howManyToStoreInMemory: 10,
        wrapperId: "recently-viewed-products",
        templateId: "recently-viewed-product-template",
        onComplete: null
    }
      , c = []
      , h = null
      , d = null
      , e = 0
      , b = {
        configuration: {
            expires: 90,
            path: "/",
            domain: window.location.hostname
        },
        name: "shopify_recently_viewed",
        write: function(i) {
            jQuery.cookie(this.name, i.join(" "), this.configuration)
        },
        read: function() {
            var i = []
              , j = jQuery.cookie(this.name);
            return j !== null && (i = j.split(" ")),
            i
        },
        destroy: function() {
            jQuery.cookie(this.name, null, this.configuration)
        },
        remove: function(k) {
            var j = this.read()
              , i = jQuery.inArray(k, j);
            i !== -1 && (j.splice(i, 1),
            this.write(j))
        }
    }
      , f = function() {
        if (h.show(),
        a.onComplete)
            try {
                a.onComplete()
            } catch (i) {}
    }
      , g = function() {
        c.length && e < a.howManyToShow ? jQuery.ajax({
            dataType: "json",
            url: "/products/" + c[0] + ".js",
            cache: !1,
            success: function(i) {
                d.tmpl(i).appendTo(h),
                c.shift(),
                e++,
                g()
            },
            error: function() {
                b.remove(c[0]),
                c.shift(),
                g()
            }
        }) : f()
    };
    return {
        resizeImage: function(m, j) {
            if (j == null)
                return m;
            if (j == "master")
                return m.replace(/http(s)?:/, "");
            var i = m.match(/\.(jpg|jpeg|gif|png|bmp|bitmap|tiff|tif)(\?v=\d+)?/i);
            if (i != null) {
                var k = m.split(i[0])
                  , l = i[0];
                return (k[0] + "_" + j + l).replace(/http(s)?:/, "")
            } else
                return null
        },
        showRecentlyViewed: function(i) {
            var i = i || {};
            jQuery.extend(a, i),
            c = b.read(),
            d = jQuery("#" + a.templateId),
            h = jQuery("#" + a.wrapperId),
            a.howManyToShow = Math.min(c.length, a.howManyToShow),
            a.howManyToShow && d.length && h.length && g()
        },
        getConfig: function() {
            return a
        },
        clearList: function() {
            b.destroy()
        },
        recordRecentlyViewed: function(l) {
            var l = l || {};
            jQuery.extend(a, l);
            var j = b.read();
            if (window.location.pathname.indexOf("/products/") !== -1) {
                var k = window.location.pathname.match(/\/products\/([a-z0-9\-]+)/)[1]
                  , i = jQuery.inArray(k, j);
                i === -1 ? (j.unshift(k),
                j = j.splice(0, a.howManyToStoreInMemory)) : (j.splice(i, 1),
                j.unshift(k)),
                b.write(j)
            }
        }
    }
}
)();
//# sourceMappingURL=/cdn/shop/t/88/assets/recently-view.js.map?v=171679271478575830981701360378
