jQuery(function($) {
    var submitDiv = $("#submitdiv");
    var WPEXContainer = $("<div id='publish-pro" + (isRtl == 1 ? " publish-pro_rtl" : "") + "'></div>").css({
        "z-index": 100,
        "position": "fixed",
        "bottom": "15px",
        "right": "55px",
        "display": "flex",
        "flex-direction": "row-reverse"
    });

    $("body").append(WPEXContainer);
    var publishButton = $("#publish").clone().removeAttr("id").appendTo(WPEXContainer).addClass("button");

    publishButton.on("click", function() {
        $(this).addClass("disabled");
        $("#publish").trigger("click");
    });

    publishButton.on("mouseenter", function() {
        $(this).css("box-shadow", "0 0 10px rgba(0,0,0,0.3)");
    });

    $(window).scroll(function() {
        var scrollPosition = $(window).scrollTop();
        var submitDivOffset = submitDiv.offset().top + submitDiv.height() - 21;
        if (scrollPosition >= submitDivOffset) {
            WPEXContainer.fadeIn("slow").css("width", (WPEXContainer.find("input").width() + 47 < 80 ? 82 : WPEXContainer.find("input").width() + 47));
        } else {
            WPEXContainer.fadeOut("slow");
        }
    });
});
