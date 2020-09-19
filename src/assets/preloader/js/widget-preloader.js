(function() {
    var list = document.querySelectorAll('[data-image]');
    for (var i = 0; i < list.length; i++) {
        var el = list[i]
        if (el.tagName != 'BODY') {
            $(el).addClass('preload')
        };
    };
    jQuery(window).bind('load', function() {
        for (var i = 0; i < list.length; i++) {
            var el = list[i],
                url = el.getAttribute('data-image');
            if (getImage(url)) {
                preload(el, url)
            }
        };
    })

    function preload(el, url) {
        if(!empty(url)){
            $(el).css({'background-image':'url(\"' + url + '\"),' + $(el).css('background-image')});
        }
        if (el.tagName != 'BODY') {
            $(el).removeClass('preload')
        };
        $(el).removeAttr('data-image')
    }

    function getImage(url) {

        if(!url || 0 === url.length){
            return false;
        }

        return new Promise(function(resolve, reject) {
            var img = new Image();
            img.onload = function() {
                resolve(url)
            };
            img.onerror = function() {
                reject(url)
            };
            img.src = url
        })
    }

    setTimeout(function() {
        for (var i = 0; i < list.length; i++) {
            var el = list[i],
                url = el.getAttribute('data-image');
            if (preload(el, url)) {
                console.log($(el))
            }
        }
    }, 3500);

    function empty(e) {
      switch (e) {
        case "":
        case 0:
        case "0":
        case null:
        case false:
        case typeof this == "undefined":
          return true;
        default:
          return false;
      }
    }
}());