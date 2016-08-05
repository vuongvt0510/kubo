

/* globals jQuery */

(function($) {
  // Selector to select only not already processed elements
  $.expr[":"].notmdproc = function(obj){
    if ($(obj).data("mdproc")) {
      return false;
    } else {
      return true;
    }
  };

  function _isChar(evt) {
    if (typeof evt.which == "undefined") {
      return true;
    } else if (typeof evt.which == "number" && evt.which > 0) {
      return !evt.ctrlKey && !evt.metaKey && !evt.altKey && evt.which != 8 && evt.which != 9;
    }
    return false;
  }
  
  function _addFormGroupFocus(element){
    $(element).closest(".form-group").addClass("is-focused");
  }
  
  function _removeFormGroupFocus(element){
    $(element).closest(".form-group").removeClass("is-focused"); // remove class from form-group
  }

  $.material =  {
    "options": {
      // These options set what will be started by $.material.init()
      "validate": true,
      "input": true,
      "ripples": true,
      "checkbox": true,
      "togglebutton": true,
      "radio": true,
      "arrive": true,
      "autofill": false,

      "withRipples": [
        ".btn:not(.btn-link)",
        ".card-image",
        ".navbar a:not(.withoutripple)",
        ".dropdown-menu a",
        ".nav-tabs a:not(.withoutripple)",
        ".withripple",
        ".pagination li:not(.active):not(.disabled) a:not(.withoutripple)"
      ].join(","),
      "inputElements": "input.form-control, textarea.form-control, select.form-control",
      "checkboxElements": ".checkbox > label > input[type=checkbox]",
      "togglebuttonElements": ".togglebutton > label > input[type=checkbox]",
      "radioElements": ".radio > label > input[type=radio]"
    },
    "checkbox": function(selector) {
      // Add fake-checkbox to material checkboxes
      $((selector) ? selector : this.options.checkboxElements)
      .filter(":notmdproc")
      .data("mdproc", true)
      .after("<span class='checkbox-material'><span class='check'></span></span>");
    },
    "togglebutton": function(selector) {
      // Add fake-checkbox to material checkboxes
      $((selector) ? selector : this.options.togglebuttonElements)
      .filter(":notmdproc")
      .data("mdproc", true)
      .after("<span class='toggle'></span>");
    },
    "radio": function(selector) {
      // Add fake-radio to material radios
      $((selector) ? selector : this.options.radioElements)
      .filter(":notmdproc")
      .data("mdproc", true)
      .after("<span class='circle'></span><span class='check'></span>");
    },
    "input": function(selector) {
      $((selector) ? selector : this.options.inputElements)
      .filter(":notmdproc")
      .data("mdproc", true)
      .each( function() {
        var $input = $(this);

        // Requires form-group standard markup (will add it if necessary)
        var $formGroup = $input.closest(".form-group"); // note that form-group may be grandparent in the case of an input-group
        if($formGroup.length === 0){
          $input.wrap("<div class='form-group'></div>");
          $formGroup = $input.closest(".form-group"); // find node after attached (otherwise additional attachments don't work)
        }

        // Legacy - Add hint label if using the old shorthand data-hint attribute on the input
        if ($input.attr("data-hint")) {
          $input.after("<p class='help-block'>" + $input.attr("data-hint") + "</p>");
          $input.removeAttr("data-hint");
        }

        // Legacy - Change input-sm/lg to form-group-sm/lg instead (preferred standard and simpler css/less variants)
        var legacySizes = {
          "input-lg": "form-group-lg",
          "input-sm": "form-group-sm"
        };
        $.each( legacySizes, function( legacySize, standardSize ) {
          if ($input.hasClass(legacySize)) {
            $input.removeClass(legacySize);
            $formGroup.addClass(standardSize);
          }
        });

        // Legacy - Add label-floating if using old shorthand <input class="floating-label" placeholder="foo">
        if ($input.hasClass("floating-label")) {
          var placeholder = $input.attr("placeholder");
          $input.attr("placeholder", null).removeClass("floating-label");
          var id = $input.attr("id");
          var forAttribute = "";
          if(id) {
            forAttribute = "for='" + id + "'";
          }
          $formGroup.addClass("label-floating");
          $input.after("<label " + forAttribute + "class='control-label'>" + placeholder + "</label>");
        }

        // Set as empty if is empty (damn I must improve this...)
        if ($input.val() === null || $input.val() == "undefined" || $input.val() === "") {
          $formGroup.addClass("is-empty");
        }

          // Add at the end of the form-group
        $formGroup.append("<span class='material-input'></span>");

        // Support for file input
        if ($formGroup.find("input[type=file]").length > 0) {
          $formGroup.addClass("is-fileinput");
        }
      });
    },
    "attachInputEventHandlers": function() {
      var validate = this.options.validate;

      // checkboxes didn't appear to bubble to the document, so we'll bind these directly
      $(".form-group .checkbox label").hover(function() {
        _addFormGroupFocus(this);
      }, function() {
        _removeFormGroupFocus(this);
      });

      $(document)
      .on("change", ".checkbox input[type=checkbox]", function() { $(this).blur(); })
      .on("keydown paste", ".form-control", function(e) {
        if(_isChar(e)) {
          $(this).closest(".form-group").removeClass("is-empty");
        }
      })
      .on("keyup change", ".form-control", function() {
        var $input = $(this);
        var $formGroup = $input.closest(".form-group");
        var isValid = (typeof $input[0].checkValidity === "undefined" || $input[0].checkValidity());

        if ($input.val() === "" && isValid) {
          $formGroup.addClass("is-empty");
        }
        else {
          $formGroup.removeClass("is-empty");
        }

        // Validation events do not bubble, so they must be attached directly to the input: http://jsfiddle.net/PEpRM/1/
        //  Further, even the bind method is being caught, but since we are already calling #checkValidity here, just alter
        //  the form-group on change.
        //
        // NOTE: I'm not sure we should be intervening regarding validation, this seems better as a README and snippet of code.
        //        BUT, I've left it here for backwards compatibility.
        if(validate) {
          if (isValid) {
            $formGroup.removeClass("has-error");
          }
          else {
            $formGroup.addClass("has-error");
          }
        }
      })
      .on("focus", ".form-control, .form-group.is-fileinput", function() {
        _addFormGroupFocus(this);
      })
      .on("blur", ".form-control, .form-group.is-fileinput", function() {
        _removeFormGroupFocus(this);
      })
      // make sure empty is added back when there is a programmatic value change.
      //  NOTE: programmatic changing of value using $.val() must trigger the change event i.e. $.val('x').trigger('change')
      .on("change", ".form-group input", function() {
        var $input = $(this);
        if($input.attr("type") == "file") {
          return;
        }

        var $formGroup = $input.closest(".form-group");
        var value = $input.val();
        if (value) {
          $formGroup.removeClass("is-empty");
        } else {
          $formGroup.addClass("is-empty");
        }
      })
      // set the fileinput readonly field with the name of the file
      .on("change", ".form-group.is-fileinput input[type='file']", function() {
        var $input = $(this);
        var $formGroup = $input.closest(".form-group");
        var value = "";
        $.each(this.files, function(i, file) {
          value += file.name + ", ";
        });
        value = value.substring(0, value.length - 2);
        if (value) {
          $formGroup.removeClass("is-empty");
        } else {
          $formGroup.addClass("is-empty");
        }
        $formGroup.find("input.form-control[readonly]").val(value);
      });
    },
    "ripples": function(selector) {
      $((selector) ? selector : this.options.withRipples).ripples();
    },
    "autofill": function() {
      // This part of code will detect autofill when the page is loading (username and password inputs for example)
      var loading = setInterval(function() {
        $("input[type!=checkbox]").each(function() {
          var $this = $(this);
          if ($this.val() && $this.val() !== $this.attr("value")) {
            $this.trigger("change");
          }
        });
      }, 100);

      // After 10 seconds we are quite sure all the needed inputs are autofilled then we can stop checking them
      setTimeout(function() {
        clearInterval(loading);
      }, 10000);
    },
    "attachAutofillEventHandlers": function() {
      // Listen on inputs of the focused form (because user can select from the autofill dropdown only when the input has focus)
      var focused;
      $(document)
      .on("focus", "input", function() {
        var $inputs = $(this).parents("form").find("input").not("[type=file]");
        focused = setInterval(function() {
          $inputs.each(function() {
            var $this = $(this);
            if ($this.val() !== $this.attr("value")) {
              $this.trigger("change");
            }
          });
        }, 100);
      })
      .on("blur", ".form-group input", function() {
        clearInterval(focused);
      });
    },
    "init": function(options) {
      this.options = $.extend({}, this.options, options);
      var $document = $(document);

      if ($.fn.ripples && this.options.ripples) {
        this.ripples();
      }
      if (this.options.input) {
        this.input();
        this.attachInputEventHandlers();
      }
      if (this.options.checkbox) {
        this.checkbox();
      }
      if (this.options.togglebutton) {
        this.togglebutton();
      }
      if (this.options.radio) {
        this.radio();
      }
      if (this.options.autofill) {
        this.autofill();
        this.attachAutofillEventHandlers();
      }

      if (document.arrive && this.options.arrive) {
        if ($.fn.ripples && this.options.ripples) {
          $document.arrive(this.options.withRipples, function() {
            $.material.ripples($(this));
          });
        }
        if (this.options.input) {
          $document.arrive(this.options.inputElements, function() {
            $.material.input($(this));
          });
        }
        if (this.options.checkbox) {
          $document.arrive(this.options.checkboxElements, function() {
            $.material.checkbox($(this));
          });
        }
        if (this.options.radio) {
          $document.arrive(this.options.radioElements, function() {
            $.material.radio($(this));
          });
        }
        if (this.options.togglebutton) {
          $document.arrive(this.options.togglebuttonElements, function() {
            $.material.togglebutton($(this));
          });
        }

      }
    }
  };

})(jQuery);

!function(a){function b(a){return"undefined"==typeof a.which?!0:"number"==typeof a.which&&a.which>0?!a.ctrlKey&&!a.metaKey&&!a.altKey&&8!=a.which&&9!=a.which:!1}function c(b){a(b).closest(".form-group").addClass("is-focused")}function d(b){a(b).closest(".form-group").removeClass("is-focused")}a.expr[":"].notmdproc=function(b){return a(b).data("mdproc")?!1:!0},a.material={options:{validate:!0,input:!0,ripples:!0,checkbox:!0,togglebutton:!0,radio:!0,arrive:!0,autofill:!1,withRipples:[".btn:not(.btn-link)",".card-image",".navbar a:not(.withoutripple)",".dropdown-menu a",".nav-tabs a:not(.withoutripple)",".withripple",".pagination li:not(.active):not(.disabled) a:not(.withoutripple)"].join(","),inputElements:"input.form-control, textarea.form-control, select.form-control",checkboxElements:".checkbox > label > input[type=checkbox]",togglebuttonElements:".togglebutton > label > input[type=checkbox]",radioElements:".radio > label > input[type=radio]"},checkbox:function(b){a(b?b:this.options.checkboxElements).filter(":notmdproc").data("mdproc",!0).after("<span class='checkbox-material'><span class='check'></span></span>")},togglebutton:function(b){a(b?b:this.options.togglebuttonElements).filter(":notmdproc").data("mdproc",!0).after("<span class='toggle'></span>")},radio:function(b){a(b?b:this.options.radioElements).filter(":notmdproc").data("mdproc",!0).after("<span class='circle'></span><span class='check'></span>")},input:function(b){a(b?b:this.options.inputElements).filter(":notmdproc").data("mdproc",!0).each(function(){var b=a(this),c=b.closest(".form-group");0===c.length&&(b.wrap("<div class='form-group'></div>"),c=b.closest(".form-group")),b.attr("data-hint")&&(b.after("<p class='help-block'>"+b.attr("data-hint")+"</p>"),b.removeAttr("data-hint"));var d={"input-lg":"form-group-lg","input-sm":"form-group-sm"};if(a.each(d,function(a,d){b.hasClass(a)&&(b.removeClass(a),c.addClass(d))}),b.hasClass("floating-label")){var e=b.attr("placeholder");b.attr("placeholder",null).removeClass("floating-label");var f=b.attr("id"),g="";f&&(g="for='"+f+"'"),c.addClass("label-floating"),b.after("<label "+g+"class='control-label'>"+e+"</label>")}(null===b.val()||"undefined"==b.val()||""===b.val())&&c.addClass("is-empty"),c.append("<span class='material-input'></span>"),c.find("input[type=file]").length>0&&c.addClass("is-fileinput")})},attachInputEventHandlers:function(){var e=this.options.validate;a(".form-group .checkbox label").hover(function(){c(this)},function(){d(this)}),a(document).on("change",".checkbox input[type=checkbox]",function(){a(this).blur()}).on("keydown paste",".form-control",function(c){b(c)&&a(this).closest(".form-group").removeClass("is-empty")}).on("keyup change",".form-control",function(){var b=a(this),c=b.closest(".form-group"),d="undefined"==typeof b[0].checkValidity||b[0].checkValidity();""===b.val()&&d?c.addClass("is-empty"):c.removeClass("is-empty"),e&&(d?c.removeClass("has-error"):c.addClass("has-error"))}).on("focus",".form-control, .form-group.is-fileinput",function(){c(this)}).on("blur",".form-control, .form-group.is-fileinput",function(){d(this)}).on("change",".form-group input",function(){var b=a(this);if("file"!=b.attr("type")){var c=b.closest(".form-group"),d=b.val();d?c.removeClass("is-empty"):c.addClass("is-empty")}}).on("change",".form-group.is-fileinput input[type='file']",function(){var b=a(this),c=b.closest(".form-group"),d="";a.each(this.files,function(a,b){d+=b.name+", "}),d=d.substring(0,d.length-2),d?c.removeClass("is-empty"):c.addClass("is-empty"),c.find("input.form-control[readonly]").val(d)})},ripples:function(b){a(b?b:this.options.withRipples).ripples()},autofill:function(){var b=setInterval(function(){a("input[type!=checkbox]").each(function(){var b=a(this);b.val()&&b.val()!==b.attr("value")&&b.trigger("change")})},100);setTimeout(function(){clearInterval(b)},1e4)},attachAutofillEventHandlers:function(){var b;a(document).on("focus","input",function(){var c=a(this).parents("form").find("input").not("[type=file]");b=setInterval(function(){c.each(function(){var b=a(this);b.val()!==b.attr("value")&&b.trigger("change")})},100)}).on("blur",".form-group input",function(){clearInterval(b)})},init:function(b){this.options=a.extend({},this.options,b);var c=a(document);a.fn.ripples&&this.options.ripples&&this.ripples(),this.options.input&&(this.input(),this.attachInputEventHandlers()),this.options.checkbox&&this.checkbox(),this.options.togglebutton&&this.togglebutton(),this.options.radio&&this.radio(),this.options.autofill&&(this.autofill(),this.attachAutofillEventHandlers()),document.arrive&&this.options.arrive&&(a.fn.ripples&&this.options.ripples&&c.arrive(this.options.withRipples,function(){a.material.ripples(a(this))}),this.options.input&&c.arrive(this.options.inputElements,function(){a.material.input(a(this))}),this.options.checkbox&&c.arrive(this.options.checkboxElements,function(){a.material.checkbox(a(this))}),this.options.radio&&c.arrive(this.options.radioElements,function(){a.material.radio(a(this))}),this.options.togglebutton&&c.arrive(this.options.togglebuttonElements,function(){a.material.togglebutton(a(this))}))}}}(jQuery);
//# sourceMappingURL=material.min.js.map
/* Copyright 2014+, Federico Zivolo, LICENSE at https://github.com/FezVrasta/bootstrap-material-design/blob/master/LICENSE.md */
/* globals jQuery, navigator */

(function($, window, document, undefined) {

  "use strict";

  /**
   * Define the name of the plugin
   */
  var ripples = "ripples";


  /**
   * Get an instance of the plugin
   */
  var self = null;


  /**
   * Define the defaults of the plugin
   */
  var defaults = {};


  /**
   * Create the main plugin function
   */
  function Ripples(element, options) {
    self = this;

    this.element = $(element);

    this.options = $.extend({}, defaults, options);

    this._defaults = defaults;
    this._name = ripples;

    this.init();
  }


  /**
   * Initialize the plugin
   */
  Ripples.prototype.init = function() {
    var $element  = this.element;

    $element.on("mousedown touchstart", function(event) {
      /**
       * Verify if the user is just touching on a device and return if so
       */
      if(self.isTouch() && event.type === "mousedown") {
        return;
      }


      /**
       * Verify if the current element already has a ripple wrapper element and
       * creates if it doesn't
       */
      if(!($element.find(".ripple-container").length)) {
        $element.append("<div class=\"ripple-container\"></div>");
      }


      /**
       * Find the ripple wrapper
       */
      var $wrapper = $element.children(".ripple-container");


      /**
       * Get relY and relX positions
       */
      var relY = self.getRelY($wrapper, event);
      var relX = self.getRelX($wrapper, event);


      /**
       * If relY and/or relX are false, return the event
       */
      if(!relY && !relX) {
        return;
      }


      /**
       * Get the ripple color
       */
      var rippleColor = self.getRipplesColor($element);


      /**
       * Create the ripple element
       */
      var $ripple = $("<div></div>");

      $ripple
      .addClass("ripple")
      .css({
        "left": relX,
        "top": relY,
        "background-color": rippleColor
      });


      /**
       * Append the ripple to the wrapper
       */
      $wrapper.append($ripple);


      /**
       * Make sure the ripple has the styles applied (ugly hack but it works)
       */
      (function() { return window.getComputedStyle($ripple[0]).opacity; })();


      /**
       * Turn on the ripple animation
       */
      self.rippleOn($element, $ripple);


      /**
       * Call the rippleEnd function when the transition "on" ends
       */
      setTimeout(function() {
        self.rippleEnd($ripple);
      }, 500);


      /**
       * Detect when the user leaves the element
       */
      $element.on("mouseup mouseleave touchend", function() {
        $ripple.data("mousedown", "off");

        if($ripple.data("animating") === "off") {
          self.rippleOut($ripple);
        }
      });

    });
  };


  /**
   * Get the new size based on the element height/width and the ripple width
   */
  Ripples.prototype.getNewSize = function($element, $ripple) {

    return (Math.max($element.outerWidth(), $element.outerHeight()) / $ripple.outerWidth()) * 2.5;
  };


  /**
   * Get the relX
   */
  Ripples.prototype.getRelX = function($wrapper,  event) {
    var wrapperOffset = $wrapper.offset();

    if(!self.isTouch()) {
      /**
       * Get the mouse position relative to the ripple wrapper
       */
      return event.pageX - wrapperOffset.left;
    } else {
      /**
       * Make sure the user is using only one finger and then get the touch
       * position relative to the ripple wrapper
       */
      event = event.originalEvent;

      if(event.touches.length === 1) {
        return event.touches[0].pageX - wrapperOffset.left;
      }

      return false;
    }
  };


  /**
   * Get the relY
   */
  Ripples.prototype.getRelY = function($wrapper, event) {
    var wrapperOffset = $wrapper.offset();

    if(!self.isTouch()) {
      /**
       * Get the mouse position relative to the ripple wrapper
       */
      return event.pageY - wrapperOffset.top;
    } else {
      /**
       * Make sure the user is using only one finger and then get the touch
       * position relative to the ripple wrapper
       */
      event = event.originalEvent;

      if(event.touches.length === 1) {
        return event.touches[0].pageY - wrapperOffset.top;
      }

      return false;
    }
  };


  /**
   * Get the ripple color
   */
  Ripples.prototype.getRipplesColor = function($element) {

    var color = $element.data("ripple-color") ? $element.data("ripple-color") : window.getComputedStyle($element[0]).color;

    return color;
  };


  /**
   * Verify if the client browser has transistion support
   */
  Ripples.prototype.hasTransitionSupport = function() {
    var thisBody  = document.body || document.documentElement;
    var thisStyle = thisBody.style;

    var support = (
      thisStyle.transition !== undefined ||
      thisStyle.WebkitTransition !== undefined ||
      thisStyle.MozTransition !== undefined ||
      thisStyle.MsTransition !== undefined ||
      thisStyle.OTransition !== undefined
    );

    return support;
  };


  /**
   * Verify if the client is using a mobile device
   */
  Ripples.prototype.isTouch = function() {
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
  };


  /**
   * End the animation of the ripple
   */
  Ripples.prototype.rippleEnd = function($ripple) {
    $ripple.data("animating", "off");

    if($ripple.data("mousedown") === "off") {
      self.rippleOut($ripple);
    }
  };


  /**
   * Turn off the ripple effect
   */
  Ripples.prototype.rippleOut = function($ripple) {
    $ripple.off();

    if(self.hasTransitionSupport()) {
      $ripple.addClass("ripple-out");
    } else {
      $ripple.animate({"opacity": 0}, 100, function() {
        $ripple.trigger("transitionend");
      });
    }

    $ripple.on("transitionend webkitTransitionEnd oTransitionEnd MSTransitionEnd", function() {
      $ripple.remove();
    });
  };


  /**
   * Turn on the ripple effect
   */
  Ripples.prototype.rippleOn = function($element, $ripple) {
    var size = self.getNewSize($element, $ripple);

    if(self.hasTransitionSupport()) {
      $ripple
      .css({
        "-ms-transform": "scale(" + size + ")",
        "-moz-transform": "scale(" + size + ")",
        "-webkit-transform": "scale(" + size + ")",
        "transform": "scale(" + size + ")"
      })
      .addClass("ripple-on")
      .data("animating", "on")
      .data("mousedown", "on");
    } else {
      $ripple.animate({
        "width": Math.max($element.outerWidth(), $element.outerHeight()) * 2,
        "height": Math.max($element.outerWidth(), $element.outerHeight()) * 2,
        "margin-left": Math.max($element.outerWidth(), $element.outerHeight()) * (-1),
        "margin-top": Math.max($element.outerWidth(), $element.outerHeight()) * (-1),
        "opacity": 0.2
      }, 500, function() {
        $ripple.trigger("transitionend");
      });
    }
  };


  /**
   * Create the jquery plugin function
   */
  $.fn.ripples = function(options) {
    return this.each(function() {
      if(!$.data(this, "plugin_" + ripples)) {
        $.data(this, "plugin_" + ripples, new Ripples(this, options));
      }
    });
  };

})(jQuery, window, document);

!function(a,b,c,d){"use strict";function e(b,c){g=this,this.element=a(b),this.options=a.extend({},h,c),this._defaults=h,this._name=f,this.init()}var f="ripples",g=null,h={};e.prototype.init=function(){var c=this.element;c.on("mousedown touchstart",function(d){if(!g.isTouch()||"mousedown"!==d.type){c.find(".ripple-container").length||c.append('<div class="ripple-container"></div>');var e=c.children(".ripple-container"),f=g.getRelY(e,d),h=g.getRelX(e,d);if(f||h){var i=g.getRipplesColor(c),j=a("<div></div>");j.addClass("ripple").css({left:h,top:f,"background-color":i}),e.append(j),function(){return b.getComputedStyle(j[0]).opacity}(),g.rippleOn(c,j),setTimeout(function(){g.rippleEnd(j)},500),c.on("mouseup mouseleave touchend",function(){j.data("mousedown","off"),"off"===j.data("animating")&&g.rippleOut(j)})}}})},e.prototype.getNewSize=function(a,b){return Math.max(a.outerWidth(),a.outerHeight())/b.outerWidth()*2.5},e.prototype.getRelX=function(a,b){var c=a.offset();return g.isTouch()?(b=b.originalEvent,1===b.touches.length?b.touches[0].pageX-c.left:!1):b.pageX-c.left},e.prototype.getRelY=function(a,b){var c=a.offset();return g.isTouch()?(b=b.originalEvent,1===b.touches.length?b.touches[0].pageY-c.top:!1):b.pageY-c.top},e.prototype.getRipplesColor=function(a){var c=a.data("ripple-color")?a.data("ripple-color"):b.getComputedStyle(a[0]).color;return c},e.prototype.hasTransitionSupport=function(){var a=c.body||c.documentElement,b=a.style,e=b.transition!==d||b.WebkitTransition!==d||b.MozTransition!==d||b.MsTransition!==d||b.OTransition!==d;return e},e.prototype.isTouch=function(){return/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)},e.prototype.rippleEnd=function(a){a.data("animating","off"),"off"===a.data("mousedown")&&g.rippleOut(a)},e.prototype.rippleOut=function(a){a.off(),g.hasTransitionSupport()?a.addClass("ripple-out"):a.animate({opacity:0},100,function(){a.trigger("transitionend")}),a.on("transitionend webkitTransitionEnd oTransitionEnd MSTransitionEnd",function(){a.remove()})},e.prototype.rippleOn=function(a,b){var c=g.getNewSize(a,b);g.hasTransitionSupport()?b.css({"-ms-transform":"scale("+c+")","-moz-transform":"scale("+c+")","-webkit-transform":"scale("+c+")",transform:"scale("+c+")"}).addClass("ripple-on").data("animating","on").data("mousedown","on"):b.animate({width:2*Math.max(a.outerWidth(),a.outerHeight()),height:2*Math.max(a.outerWidth(),a.outerHeight()),"margin-left":-1*Math.max(a.outerWidth(),a.outerHeight()),"margin-top":-1*Math.max(a.outerWidth(),a.outerHeight()),opacity:.2},500,function(){b.trigger("transitionend")})},a.fn.ripples=function(b){return this.each(function(){a.data(this,"plugin_"+f)||a.data(this,"plugin_"+f,new e(this,b))})}}(jQuery,window,document);
//# sourceMappingURL=ripples.min.js.map