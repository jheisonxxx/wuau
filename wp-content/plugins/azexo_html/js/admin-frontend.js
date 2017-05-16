(function($) {
    "use strict";
    var wp_shortcode = {
        // ### Find the next matching shortcode
        //
        // Given a shortcode `tag`, a block of `text`, and an optional starting
        // `index`, returns the next matching shortcode or `undefined`.
        //
        // Shortcodes are formatted as an object that contains the match
        // `content`, the matching `index`, and the parsed `shortcode` object.
        next: function(tag, text, index) {
            var re = wp_shortcode.regexp(tag),
                    match, result;

            re.lastIndex = index || 0;
            match = re.exec(text);

            if (!match) {
                return;
            }

            // If we matched an escaped shortcode, try again.
            if ('[' === match[1] && ']' === match[7]) {
                return wp_shortcode.next(tag, text, re.lastIndex);
            }

            result = {
                index: match.index,
                content: match[0],
                shortcode: wp_shortcode.fromMatch(match)
            };

            // If we matched a leading `[`, strip it from the match
            // and increment the index accordingly.
            if (match[1]) {
                result.content = result.content.slice(1);
                result.index++;
            }

            // If we matched a trailing `]`, strip it from the match.
            if (match[7]) {
                result.content = result.content.slice(0, -1);
            }

            return result;
        },
        // ### Replace matching shortcodes in a block of text
        //
        // Accepts a shortcode `tag`, content `text` to scan, and a `callback`
        // to process the shortcode matches and return a replacement string.
        // Returns the `text` with all shortcodes replaced.
        //
        // Shortcode matches are objects that contain the shortcode `tag`,
        // a shortcode `attrs` object, the `content` between shortcode tags,
        // and a boolean flag to indicate if the match was a `single` tag.
        replace: function(tag, text, callback) {
            return text.replace(wp_shortcode.regexp(tag), function(match, left, tag, attrs, slash, content, closing, right) {
                // If both extra brackets exist, the shortcode has been
                // properly escaped.
                if (left === '[' && right === ']') {
                    return match;
                }

                // Create the match object and pass it through the callback.
                var result = callback(wp_shortcode.fromMatch(arguments));

                // Make sure to return any of the extra brackets if they
                // weren't used to escape the shortcode.
                return result ? left + result + right : match;
            });
        },
        // ### Generate a string from shortcode parameters
        //
        // Creates a `wp_shortcode` instance and returns a string.
        //
        // Accepts the same `options` as the `wp_shortcode()` constructor,
        // containing a `tag` string, a string or object of `attrs`, a boolean
        // indicating whether to format the shortcode using a `single` tag, and a
        // `content` string.
        string: function(options) {
            return new wp_shortcode(options).string();
        },
        // ### Generate a RegExp to identify a shortcode
        //
        // The base regex is functionally equivalent to the one found in
        // `get_shortcode_regex()` in `wp-includes/shortcodes.php`.
        //
        // Capture groups:
        //
        // 1. An extra `[` to allow for escaping shortcodes with double `[[]]`
        // 2. The shortcode name
        // 3. The shortcode argument list
        // 4. The self closing `/`
        // 5. The content of a shortcode when it wraps some content.
        // 6. The closing tag.
        // 7. An extra `]` to allow for escaping shortcodes with double `[[]]`
        regexp: _.memoize(function(tag) {
            return new RegExp('\\[(\\[?)(' + tag + ')(?![\\w-])([^\\]\\/]*(?:\\/(?!\\])[^\\]\\/]*)*?)(?:(\\/)\\]|\\](?:([^\\[]*(?:\\[(?!\\/\\2\\])[^\\[]*)*)(\\[\\/\\2\\]))?)(\\]?)', 'g');
        }),
        // ### Parse shortcode attributes
        //
        // Shortcodes accept many types of attributes. These can chiefly be
        // divided into named and numeric attributes:
        //
        // Named attributes are assigned on a key/value basis, while numeric
        // attributes are treated as an array.
        //
        // Named attributes can be formatted as either `name="value"`,
        // `name='value'`, or `name=value`. Numeric attributes can be formatted
        // as `"value"` or just `value`.
        attrs: _.memoize(function(text) {
            var named = {},
                    numeric = [],
                    pattern, match;

            // This regular expression is reused from `shortcode_parse_atts()`
            // in `wp-includes/shortcodes.php`.
            //
            // Capture groups:
            //
            // 1. An attribute name, that corresponds to...
            // 2. a value in double quotes.
            // 3. An attribute name, that corresponds to...
            // 4. a value in single quotes.
            // 5. An attribute name, that corresponds to...
            // 6. an unquoted value.
            // 7. A numeric attribute in double quotes.
            // 8. An unquoted numeric attribute.
            pattern = /([\w-]+)\s*=\s*"([^"]*)"(?:\s|$)|([\w-]+)\s*=\s*'([^']*)'(?:\s|$)|([\w-]+)\s*=\s*([^\s'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|(\S+)(?:\s|$)/g;

            // Map zero-width spaces to actual spaces.
            text = text.replace(/[\u00a0\u200b]/g, ' ');

            // Match and normalize attributes.
            while ((match = pattern.exec(text))) {
                if (match[1]) {
                    named[ match[1].toLowerCase() ] = match[2];
                } else if (match[3]) {
                    named[ match[3].toLowerCase() ] = match[4];
                } else if (match[5]) {
                    named[ match[5].toLowerCase() ] = match[6];
                } else if (match[7]) {
                    numeric.push(match[7]);
                } else if (match[8]) {
                    numeric.push(match[8]);
                }
            }

            return {
                named: named,
                numeric: numeric
            };
        }),
        // ### Generate a Shortcode Object from a RegExp match
        // Accepts a `match` object from calling `regexp.exec()` on a `RegExp`
        // generated by `wp_shortcode.regexp()`. `match` can also be set to the
        // `arguments` from a callback passed to `regexp.replace()`.
        fromMatch: function(match) {
            var type;

            if (match[4]) {
                type = 'self-closing';
            } else if (match[6]) {
                type = 'closed';
            } else {
                type = 'single';
            }

            return new wp_shortcode({
                tag: match[2],
                attrs: match[3],
                type: type,
                content: match[5]
            });
        }
    };
    // Shortcode Objects
    // -----------------
    //
    // Shortcode objects are generated automatically when using the main
    // `wp_shortcode` methods: `next()`, `replace()`, and `string()`.
    //
    // To access a raw representation of a shortcode, pass an `options` object,
    // containing a `tag` string, a string or object of `attrs`, a string
    // indicating the `type` of the shortcode ('single', 'self-closing', or
    // 'closed'), and a `content` string.
    wp_shortcode = _.extend(function(options) {
        _.extend(this, _.pick(options || {}, 'tag', 'attrs', 'type', 'content'));

        var attrs = this.attrs;

        // Ensure we have a correctly formatted `attrs` object.
        this.attrs = {
            named: {},
            numeric: []
        };

        if (!attrs) {
            return;
        }

        // Parse a string of attributes.
        if (_.isString(attrs)) {
            this.attrs = wp_shortcode.attrs(attrs);

            // Identify a correctly formatted `attrs` object.
        } else if (_.isEqual(_.keys(attrs), ['named', 'numeric'])) {
            this.attrs = attrs;

            // Handle a flat object of attributes.
        } else {
            _.each(options.attrs, function(value, key) {
                this.set(key, value);
            }, this);
        }
    }, wp_shortcode);
    _.extend(wp_shortcode.prototype, {
        // ### Get a shortcode attribute
        //
        // Automatically detects whether `attr` is named or numeric and routes
        // it accordingly.
        get: function(attr) {
            return this.attrs[ _.isNumber(attr) ? 'numeric' : 'named' ][ attr ];
        },
        // ### Set a shortcode attribute
        //
        // Automatically detects whether `attr` is named or numeric and routes
        // it accordingly.
        set: function(attr, value) {
            this.attrs[ _.isNumber(attr) ? 'numeric' : 'named' ][ attr ] = value;
            return this;
        },
        // ### Transform the shortcode match into a string
        string: function() {
            var text = '[' + this.tag;

            _.each(this.attrs.numeric, function(value) {
                if (/\s/.test(value)) {
                    text += ' "' + value + '"';
                } else {
                    text += ' ' + value;
                }
            });

            _.each(this.attrs.named, function(value, name) {
                text += ' ' + name + '="' + value + '"';
            });

            // If the tag is marked as `single` or `self-closing`, close the
            // tag and ignore any additional content.
            if ('single' === this.type) {
                return text + ']';
            } else if ('self-closing' === this.type) {
                return text + ' /]';
            }

            // Complete the opening tag.
            text += ']';

            if (this.content) {
                text += this.content;
            }

            // Add the closing tag.
            return text + '[/' + this.tag + ']';
        }
    });
    function on_ready_first(completed) {
        var fired = false;
        $.holdReady(true);
        if (document.readyState === "complete") {
            setTimeout(function() {
                if (!fired) {
                    fired = true;
                    completed();
                    $.holdReady(false);
                }
            });
        } else if (document.addEventListener) {
            document.addEventListener("DOMContentLoaded", function() {
                if (!fired) {
                    fired = true;
                    completed();
                    $.holdReady(false);
                }
            }, false);
            window.addEventListener("load", function() {
                if (!fired) {
                    fired = true;
                    completed();
                    $.holdReady(false);
                }
            }, false);
        } else {
            document.attachEvent("onreadystatechange", function() {
                if (!fired) {
                    fired = true;
                    completed();
                    $.holdReady(false);
                }
            });
            window.attachEvent("onload", function() {
                if (!fired) {
                    fired = true;
                    completed();
                    $.holdReady(false);
                }
            });
        }
    }
    function makeid() {
        var text = "";
        var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
        for (var i = 0; i < 5; i++)
            text += possible.charAt(Math.floor(Math.random() * possible.length));
        return text;
    }
    function rgb2hex(rgb) {
        function hex(x) {
            return ("0" + parseInt(x).toString(16)).slice(-2);
        }
        return "#" + hex(rgb[0]) + hex(rgb[1]) + hex(rgb[2]);
    }
    function hex2rgb(hex) {
        if (hex.lastIndexOf('#') > -1) {
            hex = hex.replace(/#/, '0x');
        } else {
            hex = '0x' + hex;
        }
        var r = hex >> 16;
        var g = (hex & 0x00FF00) >> 8;
        var b = hex & 0x0000FF;
        return [r, g, b];
    }
    function html_uglify(html) {
        var results = '';
        HTMLParser(html, {
            start: function(tag, attrs, unary) {
                results += "<" + tag;
                for (var i = 0; i < attrs.length; i++) {
                    if (attrs[i].value.indexOf('"') >= 0 && attrs[i].value.indexOf("'") < 0) {
                        results += " " + attrs[i].name + "='" + attrs[i].value + "'";
                    } else {
                        results += " " + attrs[i].name + '="' + attrs[i].escaped + '"';
                    }
                }
                results += (unary ? "/" : "") + ">";
            },
            end: function(tag) {
                results += "</" + tag + ">";
            },
            chars: function(text) {
                if ($.trim(text)) {
                    results += text.replace(/[\t\r\n]*/g, '');
                }
            },
        });
        return results;
    }
    var $window = $(window);
    var $body = $('body');
    var $document = $(document);
    window.azh = $.extend({}, window.azh);
    azh.parse_query_string = function(a) {
        if (a == "")
            return {};
        var b = {};
        for (var i = 0; i < a.length; ++i)
        {
            var p = a[i].split('=');
            if (p.length != 2)
                continue;
            b[p[0]] = decodeURIComponent(p[1].replace(/\+/g, " "));
        }
        return b;
    };
    azh.fixed_inner_html = '.az-gmap';
    azh.store_html = function(wrapper) {
        var $wrapper = $(wrapper);
        var tag = wrapper.outerHTML;
        if (wrapper.innerHTML !== '') {
            tag = wrapper.outerHTML.replace('>' + wrapper.innerHTML + '<', '><');
        }
        if (tag.indexOf('></') >= 0) {
            tag = tag.split('></');
            $wrapper.data('azh-open-tag', tag[0] + '>');
            $wrapper.data('azh-close-tag', '</' + tag[1]);
        } else {
            $wrapper.data('azh-open-tag', tag);
        }
        if ($wrapper.is(azh.fixed_inner_html)) {
            $wrapper.data('azh-fixed-inner-html', wrapper.innerHTML);
        }
        $wrapper.children().each(function() {
            azh.store_html(this);
        });

    };
    azh.extract_html = function($wrapper) {
        var html = '';
        $wrapper.contents().each(function() {
            var $this = $(this);
            if ($this.data('azh-fixed-inner-html')) {
                html = html + $this.data('azh-open-tag') + $this.data('azh-fixed-inner-html') + $this.data('azh-close-tag');
            } else {
                if (this.nodeType === 1 && $this.data('azh-open-tag') && $this.data('azh-close-tag')) {
                    html = html + $this.data('azh-open-tag') + azh.extract_html($(this)) + $this.data('azh-close-tag');
                }
                if (this.nodeType === 1 && $this.data('azh-open-tag') && !$this.data('azh-close-tag')) {
                    html = html + $this.data('azh-open-tag');
                }
                if (this.nodeType === 1 && !$this.data('azh-open-tag') && !$this.data('azh-close-tag')) {
                    html = html + azh.extract_html($(this));
                }
                if (this.nodeType === 3) {
                    html = html + this.textContent;
                }
            }
        });
        return html;
    };
    azh.section_refresh = function($wrapper) {
        var html = azh.extract_html($wrapper.wrap('<div></div>').parent());
        var $section = $(html);
        azh.store_html($section.get(0));
        azh.remove_controls($wrapper);
        $section.data('azh-section-path', $wrapper.data('azh-section-path'));
        $section.data('azh-section-path').data('azh-section', $section);
        $wrapper.parent().replaceWith($section);
        azh.section_customization_init($section);
        azh.frontend_init($section);
        $window.trigger('resize');
    };
    azh.get_stored_attribute = function($element, attribute) {
        var value = false;
        HTMLParser($element.data('azh-open-tag'), {
            start: function(tag, attrs, unary) {
                for (var i = 0; i < attrs.length; i++) {
                    if (attrs[i].name === attribute) {
                        value = attrs[i].value;
                        break;
                    }
                }
            },
            end: function(tag) {
                //'</' + tag + '>';
            },
            chars: function(text) {
                //text;
            }
        });
        return value;
    };
    azh.set_stored_attribute = function($element, attribute, value) {
        var open_tag = '';
        HTMLParser($element.data('azh-open-tag'), {
            start: function(tag, attrs, unary) {
                open_tag += "<" + tag;
                var updated = false;
                for (var i = 0; i < attrs.length; i++) {
                    if (attrs[i].name == attribute) {
                        open_tag += " " + attrs[i].name + '="' + value + '"';
                        updated = true;
                    } else {
                        if (attrs[i].value.indexOf('"') >= 0 && attrs[i].value.indexOf("'") < 0) {
                            open_tag += " " + attrs[i].name + "='" + attrs[i].value + "'";
                        } else {
                            open_tag += " " + attrs[i].name + '="' + attrs[i].escaped + '"';
                        }
                    }
                }
                if (!updated) {
                    open_tag += " " + attribute + '="' + value + '"';
                }
                open_tag += (unary ? "/" : "") + ">\n";
            },
            end: function(tag) {
                //'</' + tag + '>';
            },
            chars: function(text) {
                //text;
            }
        });
        $element.data('azh-open-tag', open_tag);
    };
    azh.remove_stored_attribute = function($element, attribute) {
        var open_tag = '';
        HTMLParser($element.data('azh-open-tag'), {
            start: function(tag, attrs, unary) {
                open_tag += "<" + tag;
                for (var i = 0; i < attrs.length; i++) {
                    if (attrs[i].name !== attribute) {
                        if (attrs[i].value.indexOf('"') >= 0 && attrs[i].value.indexOf("'") < 0) {
                            open_tag += " " + attrs[i].name + "='" + attrs[i].value + "'";
                        } else {
                            open_tag += " " + attrs[i].name + '="' + attrs[i].escaped + '"';
                        }
                    }
                }
                open_tag += (unary ? "/" : "") + ">\n";
            },
            end: function(tag) {
                //'</' + tag + '>';
            },
            chars: function(text) {
                //text;
            }
        });
        $element.data('azh-open-tag', open_tag);
    };
    azh.add_to_stored_classes = function($element, class_name) {
        var classes = azh.get_stored_attribute($element, 'class');
        if (classes) {
            classes = classes.split(' ').filter(function(value) {
                return value !== ''
            });
        } else {
            classes = [];
        }
        classes.push($.trim(class_name));
        classes = classes.filter(function(value, index, self) {
            return self.indexOf(value) === index;
        }).join(' ');
        azh.set_stored_attribute($element, 'class', classes);
    };
    azh.remove_from_stored_classes = function($element, class_name) {
        var classes = azh.get_stored_attribute($element, 'class');
        if (classes) {
            classes = classes.split(' ').filter(function(value) {
                return value !== ''
            });
        } else {
            classes = [];
        }
        var index = classes.indexOf($.trim(class_name));
        if (index > -1) {
            classes.splice(index, 1);
        }
        classes = classes.join(' ');
        azh.set_stored_attribute($element, 'class', classes);
    };
    azh.get_stored_style = function($element, property) {
        var style = azh.get_stored_attribute($element, 'style');
        var properties = [];
        if (style) {
            properties = style.split(';');
        }
        style = '';
        var value = false;
        $(properties).each(function() {
            var match = /\s*([\w-]+):\s*(.*)\s*/.exec(this);
            if (match) {
                if ($.trim(match[1]) == property) {
                    value = match[2];
                    return false;
                }
            }
        });
        return value;
    };
    azh.set_stored_style = function($element, property, value) {
        var style = azh.get_stored_attribute($element, 'style');
        var properties = [];
        if (style) {
            properties = style.split(';');
        }
        style = '';
        var updated = false;
        $(properties).each(function() {
            var match = /\s*([\w-]+):\s*(.*)\s*/.exec(this);
            if (match) {
                if ($.trim(match[1]) == property) {
                    style += match[1] + ': ' + value + '; ';
                    updated = true;
                } else {
                    style += match[1] + ': ' + match[2] + '; ';
                }
            }
        });
        if (!updated) {
            style += property + ': ' + value;
        }
        azh.set_stored_attribute($element, 'style', style);
    };
    azh.remove_controls = function($wrapper) {
        $wrapper.find('.azh-controls').andSelf().filter('.azh-controls').each(function() {
            $(this).data('azh-controls').remove();
            $(this).data('azh-controls', false);
        });
        $wrapper.find('.azh-grid').andSelf().filter('.azh-grid').each(function() {
            $(this).children().each(function() {
                if ($(this).data('azh-resizer')) {
                    $(this).data('azh-resizer').remove();
                    $(this).data('azh-resizer', false);
                }
            });
        });
    };
    azh.clone_controls = function($wrapper) {
        $wrapper.find('.azh-controls').andSelf().filter('.azh-controls').each(function() {
            var $this = $(this);
            var $controls = $this.data('azh-controls');
            var $new_controls = $controls.clone(true);
            $new_controls.insertAfter($controls);
            $this.data('azh-controls', $new_controls);
            $new_controls.find('*').data('azh-linked-element', $this);
        });
        $wrapper.find('.azh-grid').andSelf().filter('.azh-grid').each(function() {
            $(this).children().each(function() {
                var $resizer = $(this).data('azh-resizer');
                if ($resizer) {
                    var $new_resizer = $resizer.clone(true);
                    $new_resizer.insertAfter($resizer);
                    $(this).data('azh-resizer', $new_resizer)
                    $resizer.data('azh-column', $(this).prev());
                    $resizer.data('azh-next-column', $(this));
                }
            });
        });
    };
    azh.refresh_controls = function($wrapper) {
        $wrapper.find('.azh-controls').andSelf().filter('.azh-controls').trigger('mousemove');
        $wrapper.find('.azh-grid').andSelf().filter('.azh-grid').trigger('mousemove');
    };
    azh.load_required_scripts = function(content) {
        $.post(azh.ajaxurl, {
            'action': 'azh_get_scripts_urls',
            'content': content,
        }, function(data) {
            if ('css' in data) {
                for (var path in data.css) {
                    $("<link/>", {
                        rel: "stylesheet",
                        type: "text/css",
                        href: data.css[path]
                    }).appendTo("head");
                }
            }
            if ('js' in data) {
                for (var path in data.js) {
                    $("<script/>", {
                        type: "text/javascript",
                        src: data.css[path]
                    }).appendTo("head");
                }
            }
        }, 'json');
    }
    azh.customization_init = function($wrapper) {
        function open_attribute_modal(options, $element, callback) {
            var $modal = $('<div class="azh-modal"></div>');
            $('<div class="azh-modal-title">' + options['title'] + '</div>').appendTo($modal);
            $('<div class="azh-modal-desc">' + options['desc'] + '</div>').appendTo($modal);
            if ('attribute' in options) {
                $('<div class="azh-modal-label">' + options['label'] + '</div>').appendTo($modal);
                if ('options' in options) {
                    var $select = $('<select class="azh-modal-control" name="' + options['attribute'] + '"></select>').appendTo($modal);
                    for (var value in options['options']) {
                        if (value == $element.attr(options['attribute'])) {
                            $('<option value="' + value + '" selected>' + options['options'][value] + '</option>').appendTo($select);
                        } else {
                            $('<option value="' + value + '">' + options['options'][value] + '</option>').appendTo($select);
                        }
                    }
                } else {
                    $('<input type="text" name="' + options['attribute'] + '" value="' + $element.attr(options['attribute']) + '" class="azh-modal-control">').appendTo($modal);
                }
            }
            if ('attributes' in options) {
                for (var name in options['attributes']) {
                    var attribute_options = options['attributes'][name];
                    $('<div class="azh-modal-label">' + attribute_options['label'] + '</div>').appendTo($modal);
                    if ('options' in attribute_options) {
                        var $select = $('<select class="azh-modal-control" name="' + name + '"></select>').appendTo($modal);
                        for (var value in attribute_options['options']) {
                            if (value == $element.attr(name)) {
                                $('<option value="' + value + '" selected>' + attribute_options['options'][value] + '</option>').appendTo($select);
                            } else {
                                $('<option value="' + value + '">' + attribute_options['options'][value] + '</option>').appendTo($select);
                            }
                        }
                    } else {
                        $('<input type="text" name="' + name + '" value="' + $element.attr(name) + '" class="azh-modal-control">').appendTo($modal);
                    }
                }
            }
            var $actions = $('<div class="azh-modal-actions"></div>').appendTo($modal);
            $('<div class="azh-modal-ok">' + azh.fi18n.ok + '</div>').appendTo($actions).on('click', function() {
                if ('attribute' in options) {
                    $element.attr(options['attribute'], $modal.find('[name="' + options['attribute'] + '"]').val());
                    azh.set_stored_attribute($element, options['attribute'], $modal.find('[name="' + options['attribute'] + '"]').val());
                }
                if ('attributes' in options) {
                    for (var name in options['attributes']) {
                        $element.attr(name, $modal.find('[name="' + name + '"]').val());
                        azh.set_stored_attribute($element, name, $modal.find('[name="' + name + '"]').val());
                    }
                }
                $.modal.close();
                callback();
                return false;
            });
            $('<div class="azh-modal-cancel">' + azh.fi18n.cancel + '</div>').appendTo($actions).on('click', function() {
                $.modal.close();
                return false;
            });
            $modal.modal({
                autoResize: true,
                overlayClose: true,
                opacity: 0,
                overlayCss: {
                    "background-color": "black"
                },
                closeClass: "azh-close",
                onClose: function() {
                    setTimeout(function() {
                        $.modal.close();
                    }, 300);
                }
            });
        }
        function open_modal(options, value, callback) {
            var $modal = $('<div class="azh-modal"></div>');
            $('<div class="azh-modal-title">' + options['title'] + '</div>').appendTo($modal);
            $('<div class="azh-modal-desc">' + options['desc'] + '</div>').appendTo($modal);
            $('<div class="azh-modal-label">' + options['label'] + '</div>').appendTo($modal);
            if ('options' in options) {
                var $select = $('<select class="azh-modal-control"></select>').appendTo($modal).on('change', function() {
                    value = $(this).find('option:selected').attr('value');
                });
                for (var value in options['options']) {
                    if (value == value) {
                        $('<option value="' + value + '" selected>' + options['options'][value] + '</option>').appendTo($select);
                    } else {
                        $('<option value="' + value + '">' + options['options'][value] + '</option>').appendTo($select);
                    }
                }
            } else {
                $('<input type="text" name="' + options['attribute'] + '" value="' + value + '" class="azh-modal-control">').appendTo($modal).on('change', function() {
                    value = $(this).val();
                });
            }
            var $actions = $('<div class="azh-modal-actions"></div>').appendTo($modal);
            $('<div class="azh-modal-ok">' + azh.fi18n.ok + '</div>').appendTo($actions).on('click', function() {
                $.modal.close();
                callback(value);
                return false;
            });
            $('<div class="azh-modal-cancel">' + azh.fi18n.cancel + '</div>').appendTo($actions).on('click', function() {
                $.modal.close();
                return false;
            });
            $modal.modal({
                autoResize: true,
                overlayClose: true,
                opacity: 0,
                overlayCss: {
                    "background-color": "black"
                },
                closeClass: "azh-close",
                onClose: function() {
                    setTimeout(function() {
                        $.modal.close();
                    }, 300);
                }
            });
        }
        function grid_editor($grid) {
            function get_column_width($column) {
                var column_patterns = [
                    /[\w\d-_]+-col-lg-([0-9]?[0-9])/gi,
                    /[\w\d-_]+-col-md-([0-9]?[0-9])/gi,
                    /[\w\d-_]+-col-sm-([0-9]?[0-9])/gi,
                    /[\w\d-_]+-col-xs-([0-9]?[0-9])/gi,
                ];
                var column_width = false;
                $(column_patterns).each(function() {
                    var column_pattern = new RegExp(this);
                    var match = null;
                    while ((match = column_pattern.exec($column.attr('class'))) != null && match.length == 2 && $.isNumeric(match[1])) {
                        column_width = match;
                        return false;
                    }
                });
                return column_width;
            }
            var full_width = true;
            $grid.children().each(function() {
                if (parseInt(get_column_width($(this))[1], 10) < 12) {
                    full_width = false;
                    return false;
                }
            });
            if (full_width) {
                return;
            }
            var $prev_column = false;
            $grid.children().each(function() {
                var $column = $(this);
                if ($prev_column) {
                    var $resizer = $('<div class="azh-width-resizer"></div>').appendTo($body);
                    $column.data('azh-resizer', $resizer);
                    $resizer.data('azh-column', $prev_column);
                    $resizer.data('azh-next-column', $column);
                    $resizer.css('left', ($column.offset().left - 20) + 'px');
                    $resizer.css('top', $column.offset().top + 'px');
                    $resizer.on('mousedown', function(e) {
                        var $resizer = $(this);
                        $resizer.addClass('azh-drag');
                        $resizer.data('pageX', e.pageX);
                        $resizer.data('azh-column').closest('.azh-grid').addClass('azh-drag');
                        e.preventDefault();
                        e.stopPropagation();
                        return false;
                    });
                }
                $prev_column = $column;
            });
            $grid.on('mousemove', function(e) {
                $grid.children().each(function() {
                    var $column = $(this);
                    var $resizer = $column.data('azh-resizer');
                    if ($resizer) {
                        $resizer.css('left', ($column.offset().left - 20) + 'px');
                        $resizer.css('top', $column.offset().top + 'px');
                    }
                });
            });
            $grid.on('click', function(e) {
                if ($(this).is('.azh-drag')) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            });
            $body.off('mouseup.grid').on('mouseup.grid', function(e) {
                $('.azh-grid.azh-drag').removeClass('azh-drag');
                $('.azh-width-resizer.azh-drag').removeClass('azh-drag');
            });
            $body.off('mousemove.grid').on('mousemove.grid', function(e) {
                if (e.buttons == 0) {
                    $('.azh-grid.azh-drag').removeClass('azh-drag');
                }
                var $resizer = $body.find('.azh-width-resizer.azh-drag');
                if ($('.azh-grid.azh-drag').length && $resizer.length) {
                    var $column = $resizer.data('azh-column');
                    var current_width = get_column_width($column);
                    var $next_column = $resizer.data('azh-next-column');
                    var next_current_width = get_column_width($next_column);
                    if (e.pageX < $resizer.offset().left && e.pageX < $resizer.data('pageX')) {
                        if (current_width[1] > 1) {
                            $column.removeClass(current_width[0]);
                            azh.remove_from_stored_classes($column, current_width[0]);
                            var less = current_width[0].replace(parseInt(current_width[0].replace(/[^\d]/g, ''), 10), parseInt(current_width[0].replace(/[^\d]/g, ''), 10) - 1);
                            $column.addClass(less);
                            azh.add_to_stored_classes($column, less);
                            $next_column.removeClass(next_current_width[0]);
                            azh.remove_from_stored_classes($next_column, next_current_width[0]);
                            var greather = next_current_width[0].replace(parseInt(next_current_width[0].replace(/[^\d]/g, ''), 10), parseInt(next_current_width[0].replace(/[^\d]/g, ''), 10) + 1);
                            $next_column.addClass(greather);
                            azh.add_to_stored_classes($next_column, greather);
                            $resizer.css('left', ($next_column.offset().left - parseInt($next_column.css('padding-left'), 10) - 5) + 'px');
                        }
                    } else {
                        if (e.pageX > ($resizer.offset().left + $resizer.width()) && e.pageX > $resizer.data('pageX')) {
                            if (next_current_width[1] > 1) {
                                $column.removeClass(current_width[0]);
                                azh.remove_from_stored_classes($column, current_width[0]);
                                var greather = current_width[0].replace(parseInt(current_width[0].replace(/[^\d]/g, ''), 10), parseInt(current_width[0].replace(/[^\d]/g, ''), 10) + 1);
                                $column.addClass(greather);
                                azh.add_to_stored_classes($column, greather);
                                $next_column.removeClass(next_current_width[0]);
                                azh.remove_from_stored_classes($next_column, next_current_width[0]);
                                var less = next_current_width[0].replace(parseInt(next_current_width[0].replace(/[^\d]/g, ''), 10), parseInt(next_current_width[0].replace(/[^\d]/g, ''), 10) - 1);
                                $next_column.addClass(less);
                                azh.add_to_stored_classes($next_column, less);
                                $resizer.css('left', ($next_column.offset().left - parseInt($next_column.css('padding-left'), 10) - 5) + 'px');
                            }
                        }
                    }
                }
                $resizer.data('pageX', e.pageX);
            });
        }
        function enable_contenteditable($element) {
            if ($element.attr('contenteditable') !== 'true' && $element.find('[contenteditable="true"]').length === 0 && $element.parents('[contenteditable="true"]').length === 0) {
                $element.attr('contenteditable', 'true');
                azh.add_to_stored_classes($element, 'az-contenteditable');
                $element.on('focus click', function(event) {
                    function blur($element) {
                        $element.children().each(function() {
                            azh.store_html(this);
                        });
                        $toolbar.hide();
                    }
                    var $this = $(this);
                    if ($this.css('display') === 'block') {
                        var $toolbar = $('.azh-editor-toolbar');
                        $toolbar.show();
                        $toolbar.css({
                            left: $this.offset().left,
                            top: $this.offset().top - $toolbar.outerHeight()
                        });
                        $element.off('blur').one('blur', function(event) {
                            blur($element);
                        });
                        $toolbar.find('input, select').off('mousedown').on('mousedown', function() {
                            var $this = $(this);
                            $element.off('blur');
                            $this.off('change').on('change', function() {
                                $element.off('blur').one('blur', function(event) {
                                    blur($element);
                                });
                                $element.trigger('focus');
                            });
                        });
                    }
                });
                $element.on('click', function(event) {
                    event.stopPropagation();
                    event.preventDefault();
                });
            }
        }
        function control_create($element, options) {
            function color_change() {
                var $control = $(this).closest('.azh-control');
                var $element = $control.data('azh-linked-element');
                var rgb = hex2rgb($control.find('input[type="color"]').val());
                var alpha = $control.find('input[type="number"]').val() / 100;
                var color = 'rgba(' + rgb[0] + ',' + rgb[1] + ',' + rgb[2] + ',' + alpha + ')';
                $element.css($control.data('azh-options')['property'], color);
                azh.set_stored_style($element, $control.data('azh-options')['property'], color);

                if ($control.data('azh-options')['refresh']) {
                    azh.section_refresh($element.closest('[data-section]'));
                }
            }
            var controls = $element.data('azh-linked-controls');
            if (!controls) {
                controls = [];
            }
            var exists = false;
            $(controls).each(function() {
                var ops = this.data('azh-options');
                if ('attribute' in options && 'attribute' in ops && options['attribute'] === ops['attribute']) {
                    exists = true;
                    return false;
                }
                if ('property' in options && 'property' in ops && options['property'] === ops['property']) {
                    exists = true;
                    return false;
                }
                if (JSON.stringify(options) === JSON.stringify(ops)) {
                    exists = true;
                    return false;
                }
            });
            if (!exists) {
                var $control = $('<div class="azh-control ' + options['control_class'] + '" data-type="' + options['control_type'] + '"></div>').on('click', function(event) {
                    event.stopPropagation();
                });
                switch (options['type']) {
                    case 'dropdown-attribute':
                        $('<label>' + options['control_text'] + '</label>').appendTo($control);
                        var $dropdown = $('<select></select>').appendTo($control).on('change', function() {
                            var $this = $(this);
                            var $control = $this.closest('.azh-control');
                            var $element = $control.data('azh-linked-element');

                            $element.attr($control.data('azh-options')['attribute'], $this.val());
                            azh.set_stored_attribute($element, $control.data('azh-options')['attribute'], $this.val());
                            if ($control.data('azh-options')['refresh']) {
                                azh.section_refresh($element.closest('[data-section]'));
                            }
                        });
                        for (var value in options['options']) {
                            $dropdown.append('<option value="' + value + '">' + options['options'][value] + '</option>');
                        }
                        $control.on('azh-init', function() {
                            var $this = $(this);
                            var $element = $this.data('azh-linked-element');
                            $this.find('select').val($element.attr($this.data('azh-options')['attribute']));
                        });
                        break;
                    case 'dropdown-style':
                        $('<label>' + options['control_text'] + '</label>').appendTo($control);
                        var $dropdown = $('<select></select>').appendTo($control).on('change', function() {
                            var $this = $(this);
                            var $control = $this.closest('.azh-control');
                            var $element = $control.data('azh-linked-element');

                            $element.css($control.data('azh-options')['property'], $this.val());
                            azh.set_stored_style($element, $control.data('azh-options')['property'], $this.val());
                            if ($control.data('azh-options')['refresh']) {
                                azh.section_refresh($element.closest('[data-section]'));
                            }
                        });
                        for (var value in options['options']) {
                            $dropdown.append('<option value="' + value + '">' + options['options'][value] + '</option>');
                        }
                        $control.on('azh-init', function() {
                            var $this = $(this);
                            var $element = $this.data('azh-linked-element');
                            $this.find('select').val($element.css($this.data('azh-options')['property']));
                        });
                        break;
                    case 'integer-attribute':
                        $('<label>' + options['control_text'] + '</label>').appendTo($control);
                        $('<input type="number" step="1"/>').appendTo($control).on('change', function() {
                            var $this = $(this);
                            var $control = $this.closest('.azh-control');
                            var $element = $control.data('azh-linked-element');

                            var value = $this.val() + ('units' in $control.data('azh-options') ? $control.data('azh-options')['units'] : '');
                            $element.attr($control.data('azh-options')['attribute'], value);
                            azh.set_stored_attribute($element, $control.data('azh-options')['attribute'], value);

                            if ($control.data('azh-options')['refresh']) {
                                azh.section_refresh($element.closest('[data-section]'));
                            }
                        }).on('mousewheel', function(e) {
                            var $this = $(this);
                            if ($this.data('focus')) {
                                var d = e.originalEvent.wheelDelta / 120;
                                $this.val($this.val() + d);
                                $this.trigger('change');
                                return false;
                            }
                        });
                        $control.on('azh-init', function() {
                            var $this = $(this);
                            var $element = $this.data('azh-linked-element');
                            $this.find('input').val(parseInt($element.attr($this.data('azh-options')['attribute']), 10));
                        });
                        break;
                    case 'input-attribute':
                        $('<label>' + options['control_text'] + '</label>').appendTo($control);
                        $('<input type="' + ('input_type' in options ? options['input_type'] : 'text') + '"/>').appendTo($control).on('change', function() {
                            var $this = $(this);
                            var $control = $this.closest('.azh-control');
                            var $element = $control.data('azh-linked-element');

                            $element.attr($control.data('azh-options')['attribute'], $this.val());
                            azh.set_stored_attribute($element, $control.data('azh-options')['attribute'], $this.val());

                            if ($control.data('azh-options')['refresh']) {
                                azh.section_refresh($element.closest('[data-section]'));
                            }
                        });
                        $control.on('azh-init', function() {
                            var $this = $(this);
                            var $element = $this.data('azh-linked-element');
                            $this.find('input').val($element.attr($this.data('azh-options')['attribute']));
                        });
                        break;
                    case 'integer-style':
                        $('<label>' + options['control_text'] + '</label>').appendTo($control);
                        $('<input type="number" step="1"/>').appendTo($control).on('change', function() {
                            var $this = $(this);
                            var $control = $this.closest('.azh-control');
                            var $element = $control.data('azh-linked-element');

                            var value = $this.val() + ('units' in $control.data('azh-options') ? $control.data('azh-options')['units'] : '');
                            $element.css($control.data('azh-options')['property'], value);
                            azh.set_stored_style($element, $control.data('azh-options')['property'], value);

                            if ($control.data('azh-options')['refresh']) {
                                azh.section_refresh($element.closest('[data-section]'));
                            }
                        }).on('mousewheel', function(e) {
                            var $this = $(this);
                            if ($this.data('focus')) {
                                var d = e.originalEvent.wheelDelta / 120;
                                $this.val($this.val() + d);
                                $this.trigger('change');
                                return false;
                            }
                        });
                        $control.on('azh-init', function() {
                            var $this = $(this);
                            var $element = $this.data('azh-linked-element');
                            $this.find('input').val(parseInt($element.get(0).style[$this.data('azh-options')['property']], 10));
                        });
                        break;
                    case 'color-style':
                        $('<label>' + options['control_text'] + '</label>').appendTo($control);
                        $control.addClass('azh-color');
                        $('<input type="number" step="1" min="0" max="100"/>').appendTo($control).on('mousewheel', function(e) {
                            var $this = $(this);
                            if ($this.data('focus')) {
                                var d = e.originalEvent.wheelDelta / 120;
                                $this.val($this.val() + d);
                                $this.trigger('change');
                                return false;
                            }
                        }).on('change', color_change);
                        $('<input type="color"/>').appendTo($control).on('change', color_change);
                        $control.on('azh-init', function() {
                            var $this = $(this);
                            var $element = $this.data('azh-linked-element');
                            var color = $element.css($this.data('azh-options')['property']);
                            var rgba = color.replace(/^rgba?\(|\s+|\)$/g, '').split(',');
                            var alpha = 100;
                            if (rgba.length == 4) {
                                alpha = Math.round(parseFloat(rgba[3]) * 100);
                            }
                            $this.find('input[type="number"]').val(alpha);
                            $this.find('input[type="color"]').val(rgb2hex(rgba));
                        });
                        break;
                    case 'toggle-attribute':
                        var $checkbox = $('<input id="' + options['control_type'] + '" type="checkbox"/>').appendTo($control).on('change', function() {
                            var $this = $(this);
                            var $control = $this.closest('.azh-control');
                            var $element = $control.data('azh-linked-element');
                            if ($this.prop('checked')) {
                                $element.attr($control.data('azh-options')['attribute'], true);
                                azh.set_stored_attribute($element, $control.data('azh-options')['attribute'], 'true');
                            } else {
                                $element.attr($control.data('azh-options')['attribute'], false);
                                azh.set_stored_attribute($element, $control.data('azh-options')['attribute'], 'false');
                            }
                            if ($control.data('azh-options')['refresh']) {
                                azh.section_refresh($element.closest('[data-section]'));
                            }
                        });
                        $('<label for="' + options['control_type'] + '">' + options['control_text'] + '</label>').appendTo($control);
                        $control.on('azh-init', function() {
                            var $this = $(this);
                            var $element = $this.data('azh-linked-element');
                            $this.find('input').prop('checked', $element.attr($this.data('azh-options')['attribute']) === 'true');
                            var id = makeid();
                            $(this).find('input').attr('id', id);
                            $(this).find('label').attr('for', id);
                        });
                        break;
                    case 'background-image':
                        $('<label>' + options['control_text'] + '</label>').appendTo($control);
                        $control.addClass('azh-image');

                        var pattern = /url\(['"]?([^'"\)]+)['"]?\)/gi;
                        var url = null;
                        while ((url = pattern.exec($element.attr('style'))) != null) {
                            $('<img src="' + (url ? url[1] : '') + '" alt="' + azh.fi18n.select_image + '"/>').appendTo($control).on('mouseup', function(event) {
                                var $this = $(this);
                                var $control = $this.closest('.azh-control');
                                var $element = $control.data('azh-linked-element');
                                if (event.which === 3) {


                                    azh.set_stored_style($element, 'background-image', azh.get_stored_style($element, 'background-image').replace($this.attr('src') ? $this.attr('src') : '/', '/'));
                                    $element.attr('style', azh.get_stored_attribute($element, 'style'));

                                    $this.attr('src', '');
                                    $this.attr('alt', azh.fi18n.select_image);


                                    if ($control.data('azh-options')['refresh']) {
                                        azh.section_refresh($element.closest('[data-section]'));
                                    }
                                } else {
                                    azh.open_image_select_dialog(event, function(url, id) {

                                        azh.set_stored_style($element, 'background-image', azh.get_stored_style($element, 'background-image').replace($this.attr('src') ? $this.attr('src') : '/', url));
                                        $element.attr('style', azh.get_stored_attribute($element, 'style'));
                                        $this.attr('src', url);

                                        if ($control.data('azh-options')['refresh']) {
                                            azh.section_refresh($element.closest('[data-section]'));
                                        }
                                    });
                                }
                            }).on('contextmenu', function(event) {
                                event.preventDefault();
                            });
                        }


                        $control.on('azh-init', function() {
                            var $this = $(this);
                            var $element = $this.data('azh-linked-element');
                            var $image = $this.find('img').first();
                            $image.detach();
                            $this.find('img').remove();
                            var pattern = /url\(['"]?([^'"\)]+)['"]?\)/gi;
                            var url = null;
                            while ((url = pattern.exec($element.attr('style'))) != null) {
                                $image.clone(true).appendTo($this).attr('src', url[1]);
                            }
                        });
                        break;
                    case 'image-attribute':
                        $('<label>' + options['control_text'] + '</label>').appendTo($control);
                        $control.addClass('azh-image');
                        var url = $element.attr(options['attribute']);
                        $('<img src="' + (url ? url : '') + '" alt="' + azh.fi18n.select_image + '"/>').appendTo($control).on('mouseup', function(event) {
                            var $this = $(this);
                            var $control = $this.closest('.azh-control');
                            var $element = $control.data('azh-linked-element');
                            if (event.which === 3) {
                                $this.attr('src', '');
                                $this.attr('alt', azh.fi18n.select_image);
                                $element.attr($control.data('azh-options')['attribute'], '');
                                azh.set_stored_attribute($element, $control.data('azh-options')['attribute'], '');
                            } else {
                                azh.open_image_select_dialog(event, function(url, id) {
                                    $this.attr('src', url);
                                    $element.attr($control.data('azh-options')['attribute'], url);
                                    azh.set_stored_attribute($element, $control.data('azh-options')['attribute'], url);
                                    if ($control.data('azh-options')['refresh']) {
                                        azh.section_refresh($element.closest('[data-section]'));
                                    }
                                });
                            }
                        }).on('contextmenu', function(event) {
                            event.preventDefault();
                        });
                        $control.on('azh-init', function() {
                            var $this = $(this);
                            var $element = $this.data('azh-linked-element');
                            var url = $element.attr($this.data('azh-options')['attribute']);
                            if (url) {
                                $this.find('img').attr('src', url);
                            }
                        });
                        break;
                    case 'exists-class':
                        var $checkbox = $('<input id="' + options['control_type'] + '" type="checkbox"/>').appendTo($control).on('change', function() {
                            var $this = $(this);
                            var $control = $this.closest('.azh-control');
                            var $element = $control.data('azh-linked-element');
                            if ($this.prop('checked')) {
                                $element.addClass($control.data('azh-options')['class']);
                                azh.add_to_stored_classes($element, $control.data('azh-options')['class']);
                            } else {
                                $element.removeClass($control.data('azh-options')['class']);
                                azh.remove_from_stored_classes($element, $control.data('azh-options')['class']);
                            }
                            if ($control.data('azh-options')['refresh']) {
                                azh.section_refresh($element.closest('[data-section]'));
                            }
                        });
                        $('<label for="' + options['control_type'] + '">' + options['control_text'] + '</label>').appendTo($control);
                        $control.on('azh-init', function() {
                            var $this = $(this);
                            var $element = $this.data('azh-linked-element');
                            $this.find('input').prop('checked', $element.hasClass($this.data('azh-options')['class']));
                            var id = makeid();
                            $(this).find('input').attr('id', id);
                            $(this).find('label').attr('for', id);
                        });
                        break;
                        break;
                    case 'exists-attribute':
                        var $checkbox = $('<input id="' + options['control_type'] + '" type="checkbox"/>').appendTo($control).on('change', function() {
                            var $this = $(this);
                            var $control = $this.closest('.azh-control');
                            var $element = $control.data('azh-linked-element');
                            if ($this.prop('checked')) {
                                $element.attr($control.data('azh-options')['attribute'], '');
                                azh.set_stored_attribute($element, $control.data('azh-options')['attribute'], '');
                            } else {
                                $element.removeAttr($control.data('azh-options')['attribute']);
                                azh.remove_stored_attribute($element, $control.data('azh-options')['attribute']);
                            }
                            if ($control.data('azh-options')['refresh']) {
                                azh.section_refresh($element.closest('[data-section]'));
                            }
                        });
                        $('<label for="' + options['control_type'] + '">' + options['control_text'] + '</label>').appendTo($control);
                        $control.on('azh-init', function() {
                            var $this = $(this);
                            var $element = $this.data('azh-linked-element');
                            $this.find('input').prop('checked', typeof $element.attr($this.data('azh-options')['attribute']) !== typeof undefined && $element.attr($this.data('azh-options')['attribute']) !== false);
                            var id = makeid();
                            $(this).find('input').attr('id', id);
                            $(this).find('label').attr('for', id);
                        });
                        break;
                }
                $control.data('azh-options', options);
                if ('menu' in options && options['menu'] == 'utility') {
                    var $section_or_element = $element.closest('[data-section].azh-controls, [data-element].azh-controls');
                    var $utility = $section_or_element.data('azh-controls').find('.azh-utility');
                    if ($utility.length === 0) {
                        $utility = $('<div class="azh-utility"></div>').appendTo($('<div class="azh-utility-wrapper"></div>').appendTo($section_or_element.data('azh-controls')));
                    }
                    $control.data('azh-linked-element', $element);
                    $control.trigger('azh-init');
                    $utility.append($control);
                } else {
                    var context = $element.data('azh-context');
                    if (!context) {
                        context = [];
                    }
                    context.push($control);
                    $element.data('azh-context', context);
                    $element.addClass('azh-context');
                }
                controls.push($control);
                $element.data('azh-linked-controls', controls);
            }
        }
        function create_element_controls($element) {
            var $element_controls = $('<div class="azh-element-controls"></div>').appendTo($body);
            $element_controls.hide();
            $element.data('azh-controls', $element_controls);
            $element_controls.data('azh-linked-element', $element);
            $element.addClass('azh-controls');
            return $element_controls;
        }
        function remove_element_button($element_controls) {
            return $('<div class="azh-remove-element" title="' + azh.fi18n.remove_element + '"></div>').appendTo($element_controls).on('click', function() {
                var $button = $(this);
                var $element = $button.data('azh-linked-element');
                $element_controls = $element.data('azh-controls');
                if ($element.parent('[data-cloneable], [data-cloneable-inline]').length === 1) {
                    if ($element.parent().children().length === 1) {
                        $element.attr('data-element', '');
                        azh.set_stored_attribute($element, 'data-element', '');
                        azh.remove_controls($element);
                        create_element_controls($element);
                        $element.empty();
                    } else {
                        azh.remove_controls($element);
                        $element.remove();
                    }
                } else {
                    var $element_wrapper = $element.closest('.az-element-wrapper');
                    if ($element_wrapper.closest('[data-cloneable], [data-cloneable-inline]').find('.az-element-wrapper').length === 1) {
                        $element.attr('data-element', '');
                        azh.set_stored_attribute($element, 'data-element', '');
                        azh.remove_controls($element);
                        create_element_controls($element);
                        $element.empty();
                    } else {
                        azh.remove_controls($element_wrapper);
                        $element_wrapper.remove();
                    }
                }

                return false;
            });
        }
        $window.trigger("azh-customization-before-init", {
            wrapper: $wrapper
        });

        $wrapper.on('contextmenu', function() {
            return false;
        });
        $wrapper.on('mouseup', function(event) {
            function fill_context_menu($context_menu, $target) {
                function fill($context_menu, $target) {
                    if ($target.is('.azh-context')) {
                        $($target.data('azh-context')).each(function() {
                            var $button = $(this);
                            if ($context_menu.children().filter('[data-type="' + $button.data('type') + '"]').length === 0) {
                                $context_menu.append($button.clone(true).data('azh-linked-element', $target));
                            }
                        });
                    }
                    $target.parents('.azh-context').each(function() {
                        var $element = $(this);
                        $($element.data('azh-context')).each(function() {
                            var $button = $(this);
                            if ($context_menu.children().filter('[data-type="' + $button.data('type') + '"]').length === 0) {
                                $context_menu.append($button.clone(true).data('azh-linked-element', $element));
                            }
                        });
                    });
                }
                fill($context_menu, $target);
                var linked_ids = $target.closest('[data-section]').data('azh-linked-ids');
                $target.parents('.azh-id-attr').andSelf().filter('.azh-id-attr').each(function() {
                    var $this = $(this);
                    if ($this.is('.azh-id-attr')) {
                        var id = $this.attr($this.data('azh-id-attr'));
                        if (id in linked_ids) {
                            $(linked_ids[id]).each(function() {
                                if (!$(this).is($this)) {
                                    fill($context_menu, $(this));
                                }
                            });
                        }
                    }
                });
                $target.parents('.azh-hash-attr').andSelf().filter('.azh-hash-attr').each(function() {
                    var $this = $(this);
                    if ($this.is('.azh-hash-attr')) {
                        var id = $this.attr($this.data('azh-hash-attr')).replace('#', '');
                        if (id in linked_ids) {
                            $(linked_ids[id]).each(function() {
                                if (!$(this).is($this)) {
                                    fill($context_menu, $(this));
                                }
                            });
                        }
                    }
                });
            }
            var $target = $(event.target);
            if (event.which === 3) {
                var $context_menu = $('.azh-context-menu').length ? $('.azh-context-menu') : $('<div class="azh-context-menu"></div>').appendTo($body);
                $context_menu.hide();
                $context_menu.empty();
                $context_menu.css('left', event.clientX + 'px');
                $context_menu.css('top', event.clientY + 'px');
                $document.off('click.azh-context-menu').on('click.azh-context-menu', function(event) {
                    if (!$target.closest('.azh-context-menu').length) {
                        $context_menu.hide();
                        $document.off('click.azh-context-menu');
                    }
                });
                fill_context_menu($context_menu, $target);
                if ($target.css('position') === 'absolute') {
                    $target.css('pointer-events', 'none');
                    fill_context_menu($context_menu, $(document.elementFromPoint(event.clientX, event.clientY)));
                    $target.css('pointer-events', '');
                }
                $target.children().each(function() {
                    var $this = $(this);
                    if ($this.css('pointer-events') === 'none') {
                        $this.css('pointer-events', 'all');
                        fill_context_menu($context_menu, $(document.elementFromPoint(event.clientX, event.clientY)));
                        $this.css('pointer-events', '');
                    }
                });

                $context_menu.children().on('mouseenter', function() {
                    var $this = $(this);
                    if ($this.data('azh-linked-element')) {
                        $this.data('azh-linked-element').addClass('azh-over');
                    }
                });
                $context_menu.children().on('mouseleave', function() {
                    var $this = $(this);
                    if ($this.data('azh-linked-element')) {
                        $this.data('azh-linked-element').removeClass('azh-over');
                    }
                });
                $context_menu.children().each(function() {
                    $(this).trigger('azh-init');
                });
                if ($context_menu.children().length) {
                    var title = '';
                    var $element_wrapper = $target.closest('[data-element]');
                    if ($element_wrapper.length) {
                        title = azh.fi18n.element + ': ' + $element_wrapper.attr('data-element');
                    } else {
                        var $section_wrapper = $target.closest('[data-section]');
                        if ($section_wrapper.length) {
                            title = azh.fi18n.section + ': ' + $section_wrapper.data('section');
                        }
                    }
                    $('<div class="azh-context-menu-title">' + title + '</div>').prependTo($context_menu);
                    $context_menu.show();
                    $context_menu.find('.azh-button').on('click', function() {
                        $context_menu.hide();
                    });
                    $context_menu.draggable({
                        handle: ".azh-context-menu-title"
                    });
                }
            }
        });

        if ('modal_options' in azh) {
            $(azh.modal_options).each(function() {
                var options = this;
                if ('selector' in options) {
                    $wrapper.find(options['selector']).each(function() {
                        var $element = $(this);
                        var $button = $('<div class="azh-modal-button ' + options['button_class'] + '" data-type="' + options['button_type'] + '" title="' + options['title'] + '">' + options['button_text'] + '</div>').on('click', function(event) {
                            var $this = $(this);
                            open_attribute_modal(options, $this.data('azh-linked-element'), function() {
                                if ($this.data('azh-options')['refresh']) {
                                    azh.section_refresh($this.data('azh-linked-element').closest('[data-section]'));
                                }
                            });
                        });
                        $button.data('azh-options', options);
                        if ('section_control' in options && options['section_control']) {
                            var $section = $element.closest('[data-section]');
                            $section.data('azh-controls').append($button);
                            $button.data('azh-linked-element', $element);
                        } else {
                            var context = $element.data('azh-context');
                            if (!context) {
                                context = [];
                            }
                            context.push($button);
                            $element.data('azh-context', context);
                            $element.addClass('azh-context');
                        }
                    });
                }
            });
        }

        $wrapper.find('.az-contenteditable').each(function() {
            enable_contenteditable($(this));
        });
        $wrapper.find('.az-inline').each(function() {
            var $this = $(this);
            if ($this.parent().children().length == 1) {
                enable_contenteditable($this.parent());
            }
        });
        $wrapper.find('*').contents().filter(function() {
            return this.nodeType === 3;
        }).each(function() {
            if ($.trim(this.textContent)) {
                enable_contenteditable($(this).parent());
            }
        });

        $wrapper.find('a:not(.azh-id-attr):not(.azh-hash-attr)').each(function() {
            var $link = $(this);
            var $button = $('<div class="azh-button azh-edit-link" data-type="azh-edit-link">' + azh.fi18n.edit_link + '</div>').on('click', function(event) {
                var $link = $(this).data('azh-linked-element');
                azh.open_link_select_dialog(event, function(url, target, title) {
                    $link.attr('href', url);
                    $link.attr('target', target);
                    azh.set_stored_attribute($link, 'href', url);
                    azh.set_stored_attribute($link, 'target', target);
                }, $link.attr('href'), $link.attr('target'), '');
            });
            var context = $link.data('azh-context');
            if (!context) {
                context = [];
            }
            context.push($button);
            $link.data('azh-context', context);
            $link.addClass('azh-context');
        });
        $wrapper.find('img, [style*="background-image"]').each(function() {
            var $image = $(this);
            if ($image.prop('tagName') !== 'IMG') {
                var url = /background-image\:[^;]*url\(['"]?([^'"\)]+)['"]?\)/gi.exec($image.attr('style'));
                if (url) {
                    if (url[1].indexOf('http') < 0) {
                        return;
                    }
                }
            }
            var $button = $('<div class="azh-button azh-edit-image" data-type="azh-edit-image">' + azh.fi18n.edit_image + '</div>').on('click', function(event) {
                var $image = $(this).data('azh-linked-element');
                azh.open_image_select_dialog(event, function(url, id) {
                    if ($image.prop('tagName') === 'IMG') {
                        $image.attr('src', url);
                        azh.set_stored_attribute($image, 'src', url);
                    } else {
                        $image.css('background-image', "url('" + url + "')");
                        azh.set_stored_style($image, 'background-image', "url('" + url + "')");
                    }
                });
            });
            var context = $image.data('azh-context');
            if (!context) {
                context = [];
            }
            context.push($button);
            $image.data('azh-context', context);
            $image.addClass('azh-context');
        });
        $wrapper.find('.az-icon').each(function() {
            var $icon = $(this);
            var $button = $('<div class="azh-button azh-edit-icon" data-type="azh-edit-icon">' + azh.fi18n.edit_icon + '</div>').on('click', function(event) {
                var $icon = $(this).data('azh-linked-element');
                azh.open_icon_select_dialog(event, $icon.attr('class'), function(icon_class) {
                    azh.load_required_scripts(icon_class);
                    $icon.attr('class', icon_class);
                    $icon.addClass('az-icon');
                    $icon.addClass('azh-context');
                    azh.set_stored_attribute($icon, 'class', $icon.attr('class'));
                });
            });
            var context = $icon.data('azh-context');
            if (!context) {
                context = [];
            }
            context.push($button);
            $icon.data('azh-context', context);
            $icon.addClass('azh-context');

        });
        $wrapper.find('[data-element]').each(function() {
            var $element = $(this);
            if ($element.children().length && ($element.attr('data-element') === '' || $element.attr('data-element') === ' ')) {
                $element.attr('data-element', 'not-empty');
            }
            var $element_controls = create_element_controls($element);
            $element.on('mouseenter', function(event) {
                var $element_controls = $(this).data('azh-controls');
                $element_controls.css('left', $(this).offset().left + 'px');
                $element_controls.css('top', $(this).offset().top + 'px');
                $element_controls.show();
            }).on('mousemove', function() {
                var $element_controls = $(this).data('azh-controls');
                $element_controls.css('left', $(this).offset().left + 'px');
                $element_controls.css('top', $(this).offset().top + 'px');
            }).on('mouseleave', function() {
                var $element_controls = $(this).data('azh-controls');
                if (!$element_controls.is(':hover')) {
                    $element_controls.hide();
                } else {
                    $element_controls.one('mouseleave', function() {
                        $element_controls.hide();
                    });
                }
            }).on('click', function() {
                var $element = $(this);
                if ($element.children().length === 0) {
                    azh.child_suggestions = [];
                    $($element.parents('[data-element]')).each(function() {
                        var $e = $('.azh-library .azh-elements .azh-element[data-path="' + $(this).attr('data-element') + '"]');
                        if ($e.length && $e.data('child-suggestions')) {
                            azh.child_suggestions = azh.child_suggestions.concat($e.data('child-suggestions'));
                        }
                    });
                    azh.show_elements_dialog(function(path, html) {
                        $element.attr('data-element', path);
                        azh.set_stored_attribute($element, 'data-element', path);
                        $element.html(html);
                        $element.children().each(function() {
                            azh.store_html(this);
                        });

                        var $remove_button = remove_element_button($element.data('azh-controls'));
                        $remove_button.data('azh-linked-element', $element);

                        azh.refresh_section_linked_ids($element.closest('[data-section]'));
                        azh.customization_init($element);
                        azh.frontend_init($element);
                        $window.trigger('resize');
                        azh.load_required_scripts('<div data-element="' + path + '">' + html + '</div>');
                    });

                    return false;
                }
            });
            if ($element.children().length !== 0) {
                var $button = remove_element_button($element_controls);
                $button.data('azh-linked-element', $element);
            }
        });
        $wrapper.find('[data-shortcode]').each(function() {
            var $shortcode = $(this);
            $shortcode.attr('title', azh.fi18n.click_to_edit_shortcode);
            $shortcode.on('mouseenter', function(event) {
                var $shortcode = $(this);
                var $hover = $('<div class="azh-shortcode"></div>').appendTo($body).on('click', function() {
                    if ('shortcode_instances' in azh && azh.shortcode_instances && $shortcode.attr('data-shortcode') in azh.shortcode_instances) {
                        var code = azh.shortcode_instances[$shortcode.attr('data-shortcode')];
                        var tags = Object.keys(azh.shortcodes).join('|');
                        var reg = wp_shortcode.regexp(tags);
                        var matches = code.match(reg);
                        var match = reg.exec(code);//str, open, name, args, self_closing, content, closing, close, offset, s
                        if (match) {
                            var name = match[2];
                            var values = wp_shortcode.attrs(match[3]).named;
                            var content = match[5];
                            var settings = azh.shortcodes[name];
                            var $form = azh.create_shortcode_form(settings, values);
                            var $modal = $('<div class="azh-modal"></div>');
                            $('<div class="azh-modal-title">' + azh.fi18n.shortcode_edit + '</div>').appendTo($modal);
                            $('<div class="azh-modal-desc"></div>').appendTo($modal);
                            $form.appendTo($modal);
                            var $actions = $('<div class="azh-modal-actions"></div>').appendTo($modal);
                            $('<div class="azh-modal-ok">' + azh.fi18n.ok + '</div>').appendTo($actions).on('click', function() {
                                var attrs = {};
                                $form.find('[data-param-name]').each(function() {
                                    if ($(this).data('get_value')) {
                                        attrs[$(this).data('param-name')] = $(this).data('get_value').call(this);
                                    }
                                });
                                var settings = $form.data('settings');
                                var shortcode = '[' + settings['base'];
                                var content = false;
                                if ('content_element' in settings && settings['content_element']) {
                                    content = ' ';
                                }
                                if ('content' in attrs) {
                                    content = attrs['content'];
                                }
                                shortcode += Object.keys(attrs).map(function(item) {
                                    if (item == 'content') {
                                        return '';
                                    } else {
                                        return ' ' + item + '="' + attrs[item] + '"';
                                    }
                                }).join('');
                                shortcode += ']';
                                if (content) {
                                    shortcode += content + '[/' + settings['base'] + ']';
                                }
                                azh.shortcode_instances[$shortcode.attr('data-shortcode')] = shortcode;
                                $.modal.close();
                                return false;
                            });
                            $('<div class="azh-modal-cancel">' + azh.fi18n.cancel + '</div>').appendTo($actions).on('click', function() {
                                $.modal.close();
                                return false;
                            });
                            $modal.modal({
                                autoResize: true,
                                overlayClose: true,
                                opacity: 0,
                                overlayCss: {
                                    "background-color": "black"
                                },
                                closeClass: "azh-close",
                                onClose: function() {
                                    setTimeout(function() {
                                        $.modal.close();
                                    }, 300);
                                }
                            });
                        }
                    }
                }).on('mouseleave', function(event) {
                    $(this).remove();
                }).css({
                    "top": $shortcode.offset().top,
                    "left": $shortcode.offset().left,
                    "width": $shortcode.outerWidth(),
                    "height": $shortcode.outerHeight()
                });
            }).on('mouseleave', function(event) {
                $('.azh-shortcode:not(:hover)').remove();
            });
        });
        $wrapper.find('[data-cloneable], [data-cloneable-inline]').each(function() {
            function get_linked_element($element) {
                var $linked_element = false;
                var linked_ids = $element.closest('[data-section]').data('azh-linked-ids');
                $element.find('.azh-id-attr, .azh-hash-attr').andSelf().filter('.azh-id-attr, .azh-hash-attr').each(function() {
                    var $this = $(this);
                    var id = false;
                    if ($this.data('azh-id-attr')) {
                        id = $this.attr($this.data('azh-id-attr'));
                    }
                    if ($this.data('azh-hash-attr')) {
                        id = $this.attr($this.data('azh-hash-attr')).replace('#', '');
                    }
                    if (id && id in linked_ids) {
                        $(linked_ids[id]).each(function() {
                            if (!$(this).is($this)) {
                                $linked_element = $(this).parentsUntil('[data-cloneable], [data-cloneable-inline]').last();
                                if ($linked_element.length === 0) {
                                    $linked_element = $(this);
                                }
                                return false;
                            }
                        });
                        if ($linked_element) {
                            return false;
                        }
                    }
                });
                return $linked_element;
            }
            var $cloneable = $(this);
            var $cloneable_controls = $('<div class="azh-cloneable-controls"></div>').appendTo($body);
            $cloneable_controls.hide();
            $cloneable.data('azh-controls', $cloneable_controls);
            $cloneable_controls.data('azh-linked-element', $cloneable);
            $cloneable.addClass('azh-controls');
            $cloneable.on('mouseenter', function(event) {
                var $cloneable_controls = $(this).data('azh-controls');
                $cloneable_controls.css('left', ($(this).offset().left + $(this).outerWidth() / 2 - 10) + 'px');
                $cloneable_controls.css('top', ($(this).offset().top + $(this).outerHeight() - 10) + 'px');
                $cloneable_controls.show();
            }).on('mousemove', function() {
                var $cloneable_controls = $(this).data('azh-controls');
                $cloneable_controls.css('left', ($(this).offset().left + $(this).outerWidth() / 2 - 10) + 'px');
                $cloneable_controls.css('top', ($(this).offset().top + $(this).outerHeight() - 10) + 'px');
            }).on('mouseleave', function() {
                var $cloneable_controls = $(this).data('azh-controls');
                if (!$cloneable_controls.is(':hover')) {
                    $cloneable_controls.hide();
                } else {
                    $cloneable_controls.one('mouseleave', function() {
                        $cloneable_controls.hide();
                    });
                }
            });
            $cloneable.children().attr('draggable', 'true');
            $body.off('mouseup.cloneable').on('mouseup.cloneable', function() {
                $wrapper.find('[data-cloneable], [data-cloneable-inline]').each(function() {
                    $(this).data('azh-drag', false);
                    $(this).find('.azh-over').removeClass('azh-over');
                });
            });
            var elements_list = true;
            $cloneable.children().each(function() {
                var $child = $(this);
                $child.on('dragstart', function(e) {
                    var $this = $(this);
                    if ($this.attr('draggable') === 'true') {
                        var $cloneable = $this.parent();
                        $cloneable.data('azh-drag', this);
                        $cloneable.addClass('azh-drag');
                        e.stopPropagation();
                    }
                });
                $child.on('dragenter', function(e) {
                    var $this = $(this);
                    var $cloneable = $this.parent();
                    if ($cloneable.data('azh-drag')) {
                        $this.addClass('azh-over');
                    } else {
                        if ($this.is('[data-element]') && $this.parent().is('[data-cloneable], [data-cloneable-inline]')) {
                            var $drag_cloneable = $('[data-cloneable].azh-drag, [data-cloneable-inline].azh-drag');
                            if ($drag_cloneable.length && !$this.parent().has($drag_cloneable.data('azh-drag')).length) {
                                $this.addClass('azh-over');
                            }
                        }
                    }
                    e.stopPropagation();
                });
                $child.on('dragover', function(e) {
                    if (e.preventDefault) {
                        e.preventDefault();
                    }
                    var $this = $(this);
                    var $cloneable = $(this).parent();
                    if ($cloneable.data('azh-drag')) {
                        $(this).addClass('azh-over');
                    } else {
                        if ($this.is('[data-element]') && $this.parent().is('[data-cloneable], [data-cloneable-inline]')) {
                            var $drag_cloneable = $('[data-cloneable].azh-drag, [data-cloneable-inline].azh-drag');
                            if ($drag_cloneable.length && !$this.parent().has($drag_cloneable.data('azh-drag')).length) {
                                $this.addClass('azh-over');
                            }
                        }
                    }
                    e.stopPropagation();
                });
                $child.on('dragleave', function(e) {
                    $(this).removeClass('azh-over');
                });
                $child.on('drop', function(e) {
                    if (e.stopPropagation) {
                        e.stopPropagation();
                    }
                    var $this = $(this);
                    var $cloneable = $this.parent();
                    var drag = $cloneable.data('azh-drag');
                    var $drag = $(drag);
                    if (drag) {
                        if ($cloneable.has(this).length) {
                            if (drag != this) {
                                var start = $cloneable.children().index(drag);
                                var end = $cloneable.children().index(this);
                                if (start >= 0 && end >= 0) {
                                    $drag.detach();
                                    if (start > end) {
                                        $drag.insertBefore(this);
                                    } else {
                                        $drag.insertAfter(this);
                                    }
                                    azh.section_refresh($drag.closest('[data-section]'));
                                }
                            }
                        }
                    } else {
                        if ($this.is('[data-element]') && $this.parent().is('[data-cloneable], [data-cloneable-inline]')) {
                            var $drag_cloneable = $('[data-cloneable].azh-drag, [data-cloneable-inline].azh-drag');
                            if ($drag_cloneable.length && !$this.parent().has($drag_cloneable.data('azh-drag')).length) {
                                var $drag = $($drag_cloneable.data('azh-drag'));
                                if ($drag.children().length) {
                                    if ($drag_cloneable.children().length === 1) {
                                        var $new_element = $drag.clone(true);
                                        $new_element.insertAfter($drag);
                                        $new_element.attr('data-element', '');
                                        azh.set_stored_attribute($new_element, 'data-element', '');
                                        create_element_controls($new_element);
                                        $new_element.empty();
                                    }
                                    $drag.detach();
                                    if ($this.children().length) {
                                        $drag.insertAfter(this);
                                    } else {
                                        $this.replaceWith($drag);
                                    }
                                    azh.section_refresh($drag.closest('[data-section]'));
                                }
                            }
                        }
                    }
                    return false;
                });
                $child.on('dragend', function(e) {
                    $('[data-cloneable], [data-cloneable-inline]').children().each(function() {
                        $(this).removeClass('azh-over');
                    });
                    $('[data-cloneable].azh-drag, [data-cloneable-inline].azh-drag').each(function() {
                        $(this).removeClass('azh-drag');
                        $(this).data('azh-drag', false);
                    });
                });
                var context = $child.data('azh-context');
                if (!context) {
                    context = [];
                }
                var $button = $('<div class="azh-button azh-clone" data-type="azh-clone">' + azh.fi18n.clone + '</div>').on('click', function(event) {
                    var $element = $(this).data('azh-linked-element');
                    var $section = $element.closest('[data-section]');
                    var $new_element = $element.clone(true);
                    $new_element.insertAfter($element);
                    azh.clone_controls($new_element);

                    var old_id = false;
                    var $old_handle_id = $element.find('.azh-id-attr, .azh-hash-attr').andSelf().filter('.azh-id-attr, .azh-hash-attr');
                    var unoque_id = makeid();
                    var $handle_id = $new_element.find('.azh-id-attr, .azh-hash-attr').andSelf().filter('.azh-id-attr, .azh-hash-attr');
                    if ($handle_id.length) {
                        $handle_id.each(function() {
                            var $this = $(this);
                            if ($this.data('azh-id-attr')) {
                                old_id = $this.attr($this.data('azh-id-attr'));
                                $this.attr($this.data('azh-id-attr'), unoque_id);
                                azh.set_stored_attribute($this, $this.data('azh-id-attr'), unoque_id);
                            }
                            if ($this.data('azh-hash-attr')) {
                                old_id = $this.attr($this.data('azh-hash-attr')).replace('#', '');
                                $this.attr($this.data('azh-hash-attr'), '#' + unoque_id);
                                azh.set_stored_attribute($this, $this.data('azh-hash-attr'), '#' + unoque_id);
                            }
                        });
                        if ($handle_id.length == 1) {
                            var $new_linked_element = false;
                            var linked_ids = $element.closest('[data-section]').data('azh-linked-ids');
                            if (old_id && old_id in linked_ids && linked_ids[old_id]) {
                                $(linked_ids[old_id]).each(function() {
                                    var $this = $(this);
                                    if (!$this.is($old_handle_id)) {
                                        var $linked_element = $this.parentsUntil('[data-cloneable], [data-cloneable-inline]').last();
                                        if ($linked_element.length === 0) {
                                            $linked_element = $this;
                                        }
                                        $new_linked_element = $linked_element.clone(true);
                                        $new_linked_element.insertAfter($linked_element);
                                        var $linked_handle_id = $new_linked_element.find('.azh-id-attr, .azh-hash-attr').andSelf().filter('.azh-id-attr, .azh-hash-attr');

                                        if ($linked_handle_id.data('azh-id-attr')) {
                                            $linked_handle_id.attr($linked_handle_id.data('azh-id-attr'), unoque_id);
                                            azh.set_stored_attribute($linked_handle_id, $linked_handle_id.data('azh-id-attr'), unoque_id);
                                        }
                                        if ($linked_handle_id.data('azh-hash-attr')) {
                                            $linked_handle_id.attr($linked_handle_id.data('azh-hash-attr'), '#' + unoque_id);
                                            azh.set_stored_attribute($linked_handle_id, $linked_handle_id.data('azh-hash-attr'), '#' + unoque_id);
                                        }
                                        return false;
                                    }
                                });
                                if ($new_linked_element) {
                                    linked_ids[unoque_id] = [
                                        $new_element.find('.azh-id-attr, .azh-hash-attr').andSelf().filter('.azh-id-attr, .azh-hash-attr'),
                                        $new_linked_element.find('.azh-id-attr, .azh-hash-attr').andSelf().filter('.azh-id-attr, .azh-hash-attr')
                                    ];
                                    linked_ids[old_id] = false;
                                }
                            }
                        }
                    }
                    azh.section_refresh($section);
                });
                context.push($button);
                $button = $('<div class="azh-button azh-remove" data-type="azh-remove">' + azh.fi18n.remove + '</div>').on('click', function(event) {
                    var $element = $(this).data('azh-linked-element');
                    var $cloneable = $element.closest('[data-cloneable], [data-cloneable-inline]');
                    if ($cloneable.is('.az-elements-list')) {
                        var $controls = $element.data('azh-controls');
                        $controls.find('.azh-remove-element').click();
                    } else {
                        var $section = $element.closest('[data-section]');
                        var $linked_element = get_linked_element($element);
                        if ($linked_element) {
                            azh.remove_controls($linked_element);
                            $linked_element.remove();
                        }
                        azh.remove_controls($element);
                        $element.remove();
                        azh.section_refresh($section);
                    }
                });
                context.push($button);
                $child.data('azh-context', context);
                $child.addClass('azh-context');
                if (!$child.is('[data-element]') && $child.find('[data-element]').length !== 1) {
                    elements_list = false;
                }
            });
            if (elements_list || $cloneable.is('.az-elements-list')) {
                $cloneable.addClass('az-elements-list');
                azh.add_to_stored_classes($cloneable, 'az-elements-list');
                $cloneable.children().each(function() {
                    var $child = $(this);
                    $child.addClass('az-element-wrapper');
                });
                var $add_button = $('<div class="azh-add-element" title="' + azh.fi18n.add_element + '"></div>').appendTo($cloneable_controls).on('click', function() {
                    var $button = $(this);
                    var $cloneable = $button.data('azh-linked-element');
                    var $element_wrapper = $cloneable.find('.az-element-wrapper').first();
                    var $new_element_wrapper = false;
                    if ($element_wrapper.is('[data-element]')) {
                        if ($element_wrapper.children().length === 0) {
                            $new_element_wrapper = $element_wrapper;
                        } else {
                            $new_element_wrapper = $element_wrapper.clone(true);
                            $new_element_wrapper.insertAfter($element_wrapper);
                        }
                    } else {
                        if ($element_wrapper.find('[data-element]').children().length === 0) {
                            $new_element_wrapper = $element_wrapper;
                        } else {
                            $new_element_wrapper = $element_wrapper.clone(true);
                            $new_element_wrapper.insertAfter($element_wrapper);
                        }
                    }
                    var $new_element = $new_element_wrapper.is('[data-element]') ? $new_element_wrapper : $new_element_wrapper.find('[data-element]');

                    $new_element.attr('data-element', '');
                    azh.set_stored_attribute($new_element, 'data-element', '');
                    create_element_controls($new_element);
                    $new_element.empty();

                    $new_element.trigger('click');

                    return false;
                });
                $add_button.data('azh-linked-element', $cloneable);
            }
        });
        $wrapper.find('[class*="-col-xs-"],[class*="-col-sm-"],[class*="-col-md-"],[class*="-col-lg-"]').each(function() {
            $(this).parent().addClass('azh-grid');
        });
        $wrapper.find('.azh-grid').each(function() {
            grid_editor($(this));
        });
        $wrapper.find('[data-isotope-items]').each(function() {
            var $isotope = $(this);
            var $items_parents = $isotope.parents();
            var min = false;
            var $filters = false;
            $wrapper.find('[data-isotope-filters]').each(function() {
                var $cca = $items_parents.has($(this)).first();
                var index = $items_parents.index($cca);
                if ($filters) {
                    if (min > index) {
                        min = index;
                        $filters = $(this);
                    }
                } else {
                    min = index;
                    $filters = $(this);
                }
            });
            $isotope.data('filters', $filters);
            $isotope.children().each(function() {
                var $item = $(this);
                var $item_controls = $('<div class="azh-item-controls"></div>').appendTo($body);
                $item_controls.hide();
                $item.data('azh-controls', $item_controls);
                $item_controls.data('azh-linked-element', $item);
                $item_controls.addClass('azh-controls');
                $item.on('mouseenter', function(event) {
                    var $item_controls = $(this).data('azh-controls');
                    $item_controls.css('left', $(this).offset().left + 'px');
                    $item_controls.css('top', $(this).offset().top + 'px');
                    $item_controls.show();
                }).on('mousemove', function() {
                    var $item_controls = $(this).data('azh-controls');
                    $item_controls.css('left', $(this).offset().left + 'px');
                    $item_controls.css('top', $(this).offset().top + 'px');
                }).on('mouseleave', function() {
                    var $item_controls = $(this).data('azh-controls');
                    if (!$item_controls.is(':hover')) {
                        $item_controls.hide();
                    } else {
                        $item_controls.on('mouseleave', function() {
                            $item_controls.hide();
                        });
                    }
                });
                $('<div class="azh-edit-tags" title="' + azh.fi18n.edit_tags + '"></div>').appendTo($item_controls).on('click', function() {
                    var $item_controls = $(this).closest('.azh-controls');
                    $item_controls.hide();
                    var $item = $item_controls.data('azh-linked-element');
                    var $isotope = $item.closest('[data-isotope-items]');
                    var $filters = $isotope.data('filters');
                    var filters = {};
                    $filters.find('*').contents().filter(function() {
                        return this.nodeType === 3 && $.trim(this.textContent);
                    }).each(function() {
                        filters[$.trim(this.textContent)] = $.trim($(this).closest('[data-filter]').attr('data-filter'));
                    });
                    var old_tags = [];
                    for (var label in filters) {
                        var selector = filters[label];
                        if (selector != '*') {
                            if ($item.is(selector)) {
                                old_tags.push(label);
                            }
                        }
                    }
                    open_modal({
                        "title": "Edit item tags",
                        "desc": "Change the tags of this item (separated by comma)",
                        "label": "Tags",
                    }, old_tags.join(', '), function(new_tags) {
                        new_tags = new_tags.split(',');
                        var exists_tags = [];
                        var not_exists_tags = [];
                        $(new_tags).each(function() {
                            var tag = $.trim(this);
                            var exists = false;
                            for (var label in filters) {
                                if (label === tag) {
                                    exists_tags.push(tag);
                                    exists = true;
                                    break;
                                }
                            }
                            if (!exists) {
                                not_exists_tags.push(tag);
                            }
                        });
                        $(old_tags).each(function() {
                            $item.removeClass(filters[this].replace('.', ''));
                            azh.remove_from_stored_classes($item, filters[this].replace('.', ''));
                        });
                        $(exists_tags).each(function() {
                            $item.addClass(filters[this].replace('.', ''));
                            azh.add_to_stored_classes($item, filters[this].replace('.', ''));
                        });
                        $(not_exists_tags).each(function() {
                            var c = this.replace(/\s/, '-').toLowerCase();
                            $item.addClass(c);
                            azh.add_to_stored_classes($item, c);
                            var $new_filter = $filters.find('[data-filter="*"]').clone(true);
                            $new_filter.appendTo($filters);
                            $new_filter.removeClass('az-is-checked');
                            azh.remove_from_stored_classes($new_filter, 'az-is-checked');
                            $new_filter.attr('data-filter', '.' + c);
                            azh.set_stored_attribute($new_filter, 'data-filter', '.' + c);
                            $new_filter.find('*').andSelf().contents().filter(function() {
                                return this.nodeType === 3 && $.trim(this.textContent);
                            }).get(0).textContent = this;
                        });
                    });
                    return false;
                });
            });
        });

        if ('controls_options' in azh) {
            $(azh.controls_options).each(function() {
                var options = this;
                if ('selector' in options) {
                    $wrapper.find(options['selector']).each(function() {
                        control_create($(this), options);
                    });
                }
            });
            $(azh.controls_options).each(function() {
                var options = this;
                if (!('selector' in options)) {
                    switch (options['type']) {
                        case 'dropdown-attribute':
                        case 'integer-attribute':
                        case 'toggle-attribute':
                        case 'image-attribute':
                            $wrapper.find('[' + options['attribute'] + ']').each(function() {
                                if (azh.get_stored_attribute($(this), options['attribute']) !== false) {
                                    control_create($(this), options);
                                }
                            });
                            break;
                        case 'dropdown-style':
                        case 'integer-style':
                        case 'color-style':
                            $wrapper.find('[style]').each(function() {
                                var $this = $(this);
                                var property_pattern = new RegExp('(^' + options['property'] + '|[ \\"\\\'\\;]' + options['property'] + ')\\s*:', 'i');
                                if ($this.attr('style').match(property_pattern)) {
                                    var style = azh.get_stored_attribute($this, 'style');
                                    if (style !== false) {
                                        if (style.match(property_pattern)) {
                                            control_create($(this), options);
                                        }
                                    }
                                }
                            });
                            break;
                    }
                }
            });
        }

        $window.trigger("azh-customization-after-init", {
            wrapper: $wrapper
        });
    };
    azh.refresh_section_linked_ids = function($wrapper) {
        var ids = {};
        var id_attributes = ['id', 'for'];
        var hash_attributes = ['href', 'data-target', 'data-id'];
        $(id_attributes).each(function() {
            var id_attribute = this;
            $wrapper.find('[' + id_attribute + ']').each(function() {
                var id = $(this).attr(id_attribute);
                if (!(id in ids)) {
                    ids[id] = [];
                }
                $(this).addClass('azh-id-attr');
                $(this).data('azh-id-attr', id_attribute);
                ids[id].push($(this));
            });
        });
        $(hash_attributes).each(function() {
            var hash_attribute = this;
            $wrapper.find('[' + hash_attribute + '^="#"]').each(function() {
                var id = $(this).attr(hash_attribute).replace('#', '');
                if ($.trim(id)) {
                    if (!(id in ids)) {
                        ids[id] = [];
                    }
                    $(this).addClass('azh-hash-attr');
                    $(this).data('azh-hash-attr', hash_attribute);
                    ids[id].push($(this));
                }
            });
        });
        var linked_ids = {};
        for (var id in ids) {
            if (ids[id].length > 1) {
                linked_ids[id] = ids[id];
            }
        }
        $wrapper.data('azh-linked-ids', linked_ids);
    };
    azh.section_customization_init = function($wrapper) {
        var $section_controls = $('<div class="azh-section-controls"></div>').appendTo($body);
        $section_controls.hide();
        $wrapper.data('azh-controls', $section_controls);
        $wrapper.addClass('azh-controls');
        $wrapper.on('mouseenter', function(event) {
            $section_controls.show();
            $section_controls.css('right', ($body.prop("clientWidth") - $wrapper.offset().left - $wrapper.outerWidth()) + 'px');
            $section_controls.css('top', ($wrapper.offset().top) + 'px');
        }).on('mousemove', function() {
            $section_controls.css('right', ($body.prop("clientWidth") - $wrapper.offset().left - $wrapper.outerWidth()) + 'px');
            $section_controls.css('top', ($wrapper.offset().top) + 'px');
        }).on('mouseleave', function() {
            if (!$section_controls.is(':hover')) {
                $section_controls.hide();
            } else {
                $section_controls.on('mouseleave', function() {
                    $section_controls.hide();
                });
            }
        });
        azh.refresh_section_linked_ids($wrapper);
        azh.customization_init($wrapper);
    };
    azh.focus = function($target, duration) {
        var focus_padding = 0;
        if ($('.azh-focus').length == 0) {
            $('<div class="azh-focus"><div class="top"></div><div class="right"></div><div class="bottom"></div><div class="left"></div></div>').appendTo($body).on('click', function() {
                $('.azh-focus').remove();
                return false;
            });
            $('.azh-focus .top, .azh-focus .right, .azh-focus .bottom, .azh-focus .left').css({
                'z-index': '999999',
                'position': 'fixed',
                'background-color': 'black',
                'opacity': '0.4'
            });
        }
        var $top = $('.azh-focus .top');
        var $right = $('.azh-focus .right');
        var $bottom = $('.azh-focus .bottom');
        var $left = $('.azh-focus .left');
        var target_top = $target.offset()['top'] - focus_padding - $body.scrollTop();
        var target_left = $target.offset()['left'] - focus_padding;
        var target_width = $target.outerWidth() + focus_padding * 2;
        var target_height = $target.outerHeight() + focus_padding * 2;
        $top.stop().animate({
            top: 0,
            left: 0,
            right: 0,
            height: target_top,
        }, duration, 'linear');
        $right.stop().animate({
            top: target_top,
            left: target_left + target_width,
            right: 0,
            height: target_height,
        }, duration, 'linear');
        $bottom.stop().animate({
            top: target_top + target_height,
            left: 0,
            right: 0,
            bottom: 0,
        }, duration, 'linear');
        $left.stop().animate({
            top: target_top,
            left: 0,
            height: target_height,
            width: target_left,
        }, duration, 'linear', function() {
        });
        if (duration > 0) {
            setTimeout(function() {
                $window.on('scroll.focus', function() {
                    $('.azh-focus').remove();
                    $window.off('scroll.focus');
                });
                $('.azh-focus .top, .azh-focus .right, .azh-focus .bottom, .azh-focus .left').stop().animate({
                    'opacity': '0'
                }, duration * 10);
                setTimeout(function() {
                    $window.trigger('scroll');
                }, duration * 10);
            }, duration);
        }
    };
    azh.structure_refresh = function($content) {
        $('.azh-structure').empty();
        $content.find('[data-section]').each(function() {
            var section_path = $('<div class="azh-section-path">' + $(this).data('section') + '</div>').appendTo($('.azh-structure'));
            $(section_path).data('azh-section', $(this));
            $(this).data('azh-section-path', section_path);
            $('<div class="azh-remove"></div>').appendTo(section_path).on('click', function() {
                azh.remove_controls($(section_path).data('azh-section'));
                $(section_path).data('azh-section').remove();
                $(section_path).remove();
                return false;
            });
            $(section_path).on('click', function() {
                var section = $(this).data('azh-section');
                $('body, html').stop().animate({
                    'scrollTop': $(section).offset().top - $(window).height() / 2 + $(section).height() / 2
                }, 300);
                setTimeout(function() {
                    $('<div class="azh-overlay"></div>').appendTo($body);
                    azh.focus($('.azh-overlay'), 0);
                    setTimeout(function() {
                        $('.azh-overlay').remove();
                        azh.focus(section, 300);
                    }, 0);
                }, 300);
                return false;
            });
        });
        $('.azh-structure').sortable({
            placeholder: 'azh-placeholder',
            forcePlaceholderSize: true,
            update: function(event, ui) {
                var section = $(ui.item).data('azh-section');
                $(section).detach();
                if ($(ui.item).next().length) {
                    var next_section = $(ui.item).next().data('azh-section');
                    $(next_section).before(section);
                } else {
                    if ($(ui.item).prev().length) {
                        var prev_section = $(ui.item).prev().data('azh-section');
                        $(prev_section).after(section);
                    }
                }
            },
            over: function(event, ui) {
                ui.placeholder.attr('class', ui.helper.attr('class'));
                ui.placeholder.removeClass('ui-sortable-helper');

                ui.placeholder.attr('style', ui.helper.attr('style'));
                ui.placeholder.css('position', 'relative');
                ui.placeholder.css('z-index', 'auto');
                ui.placeholder.css('left', 'auto');
                ui.placeholder.css('top', 'auto');

                ui.placeholder.addClass('azh-placeholder');
            }
        });
        if ($('.azh-structure').length) {
            $('.azh-structure').scrollTop($('.azh-structure')[0].scrollHeight);
        }
    };
    azh.library_init = function($content) {
        function filters_change() {
            var category = $(categories).find('option:selected').val();
            var tag = $(tags_select).find('option:selected').val();
            if (category == '' && tag == '') {
                $('.azh-library .azh-sections .azh-section').show();
            } else {
                if (category != '' && tag == '') {
                    $('.azh-library .azh-sections .azh-section').hide();
                    $('.azh-library .azh-sections .azh-section[data-path^="' + category + '"]').show();
                }
                if (category == '' && tag != '') {
                    $('.azh-library .azh-sections .azh-section').hide();
                    $('.azh-library .azh-sections .azh-section.' + tag).show();
                }
                if (category != '' && tag != '') {
                    $('.azh-library .azh-sections .azh-section').show();
                    $('.azh-library .azh-sections .azh-section:not([data-path^="' + category + '"])').hide();
                    $('.azh-library .azh-sections .azh-section:not(.' + tag + ')').hide();
                }
            }
        }
        azh.tags = {};
        var files_tags = {};
        for (var dir in azh.dirs_options) {
            if ('tags' in azh.dirs_options[dir]) {
                for (var file in azh.dirs_options[dir].tags) {
                    var tags = azh.dirs_options[dir].tags[file].split(',').map(function(tag) {
                        azh.tags[$.trim(tag).toLowerCase()] = true;
                        return $.trim(tag).toLowerCase();
                    });
                    files_tags[dir + '/' + file] = tags;
                }
            }
        }
        $('.azh-library .azh-sections .azh-section').each(function() {
            var key = $(this).data('dir') + '/' + $(this).data('path');
            if (key in files_tags) {
                $(this).addClass(files_tags[key].join(' '));
            }
        });
        $('.azh-library .azh-elements .azh-element').each(function() {
            var key = $(this).data('dir') + '/' + $(this).data('path');
            if (key in files_tags) {
                $(this).addClass(files_tags[key].join(' '));
            }
        });
        var child_suggestions = {};
        for (var dir in azh.dirs_options) {
            if ('child-suggestions' in azh.dirs_options[dir]) {
                for (var file in azh.dirs_options[dir]['child-suggestions']) {
                    var path = dir + '/' + file;
                    if (!(path in child_suggestions)) {
                        child_suggestions[path] = [];
                    }
                    $(azh.dirs_options[dir]['child-suggestions'][file]).each(function() {
                        child_suggestions[path].push(dir + '/' + this);
                    });
                }
            }
        }
        var child_suggestions_elements = {};
        for (var path in child_suggestions) {
            $(child_suggestions[path]).each(function() {
                var suggestion = this;
                $('.azh-library .azh-elements .azh-element').each(function() {
                    var key = $(this).data('dir') + '/' + $(this).data('path');
                    if (key == suggestion) {
                        if (!(path in child_suggestions_elements)) {
                            child_suggestions_elements[path] = [];
                        }
                        child_suggestions_elements[path].push(this);
                    }
                });
            });
        }
        $('.azh-library .azh-elements .azh-element').each(function() {
            var key = $(this).data('dir') + '/' + $(this).data('path');
            if (key in child_suggestions_elements) {
                $(this).data('child-suggestions', child_suggestions_elements[key]);
            }
        });
        $('.azh-add-section').off('click').on('click', function() {
            $('.azh-sections .azh-section.azh-fuzzy').removeClass('azh-fuzzy');
            if ($('.azh-library').css('display') == 'none') {
                $('.azh-structure').animate({
                    'max-height': "100px"
                }, 400, function() {
                    $('.azh-structure').scrollTop($('.azh-structure')[0].scrollHeight);
                });
                $('.azh-sections').height($('#azexo-html-library > .azh-builder').height() - $('.azh-structure').outerHeight() - 120);
                $('.azh-library').slideDown();
                $(this).text($(this).data('close'));
            } else {
                $('.azh-structure').animate({
                    'max-height': "600px"
                }, 400);
                $('.azh-library').slideUp();
                $(this).text($(this).data('open'));
            }
            return false;
        });
        $('.azh-copy-sections-list').off('click').on('click', function() {
            var paths = []
            $('.azh-structure .azh-section-path').each(function() {
                paths.push($(this).text());
            });
            var $temp = $("<input>");
            $("body").append($temp);
            $temp.val(paths.join('|')).select();
            document.execCommand("copy");
            $temp.remove();
            alert(azh.i18n.copied);
            return false;
        });
        $('.azh-insert-sections-list').off('click').on('click', function() {
            var sections = prompt(azh.i18n.paste_sections_list_here);
            if ($.trim(sections) !== '') {
                $(sections.split('|')).each(function() {
                    $('.azh-library .azh-sections .azh-section[data-path="' + this + '"]').click();
                });
            }
            return false;
        });

        var categories = $('.azh-library .azh-categories').off('change').on('change', filters_change);
        var tags_select = $('<select></select>').appendTo('.azh-library-filters').on('change', filters_change);
        $('<option selected value="">' + azh.i18n.filter_by_tag + '</option>').appendTo(tags_select);
        for (var tag in azh.tags) {
            $('<option value="' + tag + '">' + tag + '</option>').appendTo(tags_select);
        }
        $('.azh-library .azh-sections .azh-section').off('click').on('click', function() {
            var preview = this;
            $.get($(preview).data('url'), function(data) {
                var section_exists = false;
                data = data.replace(/{{azh-uri}}/g, $(preview).data('dir-uri'));
                data = data.replace(/\[\[([^\]]+)\]\]/g, '');
                data = data.replace(/\[azh_text\]/g, '');
                data = data.replace(/\[\/azh_text\]/g, '');
                data = html_uglify(data);
                var $section = $('<div data-section="' + $(preview).data('path') + '">' + data + '</div>');
                azh.store_html($section.get(0));
                $content.append($section);
                azh.section_customization_init($section);
                azh.frontend_init($section);
                $window.trigger('resize');
                azh.structure_refresh($content);
                var pattern = /(data-section|data-element)=[\'"]([^\'"]+)[\'"]/gi;
                var match = null;
                azh.load_required_scripts('<div data-section="' + $(preview).data('path') + '">' + data + '</div>');
            });
            return false;
        });
        $('.azh-library .azh-sections .azh-section.general').each(function() {
            var section = $(this).clone(true);
            $('#azexo-html-library > .azh-general').append(section);
        });
        $('.azh-library-actions > div:not(.azh-save)').on('click', function() {
            $('#azexo-html-library').find('.azh-active').removeClass('azh-active');
            var tab = $.trim($(this).attr('class'));
            $(this).addClass('azh-active');
            $('#azexo-html-library > .' + tab).addClass('azh-active');
        });
        $('.azh-library-actions > .azh-save').on('click', function() {
            var html = azh.extract_html(azh.content_wrapper);
            $.post(azh.ajaxurl, {
                'action': 'azh_save',
                'post_id': azh.post_id,
                'shortcodes': azh.shortcode_instances,
                'content': html,
            }, function(data) {
                if (data == 1) {
                    alert(azh.fi18n.saved);
                }
            });
        });
    }
    azh.tabs = function($wrapper) {
        $wrapper.each(function() {
            var $tabs = $(this);
            if (!$tabs.data('azh-tabs')) {
                $tabs.find('> div:first-child > span > a[href^="#"]').on('click', function(event) {
                    var $this = $(this);
                    event.preventDefault();
                    $this.parent().addClass("azh-active");
                    $this.parent().siblings().removeClass("azh-active");
                    var tab = $this.attr("href");
                    $tabs.find('> div:last-child > div').not(tab).css("display", "none");
                    $(tab).fadeIn();
                });
                $tabs.find('> div:first-child > span:first-child > a[href^="#"]').click();
                $tabs.data('azh-tabs', true);
            }
        });
    };
    azh.show_elements_dialog = function(callback) {
        function filters_change() {
            var category = $categories_select.find('option:selected').val();
            var tag = $tags_select.find('option:selected').val();
            if (category == '' && tag == '') {
                $('.azh-elements-form .azh-elements-list .azh-element').css('display', 'inline-block');
            } else {
                if (category != '' && tag == '') {
                    $('.azh-elements-form .azh-elements-list .azh-element').css('display', 'none');
                    $('.azh-elements-form .azh-elements-list .azh-element[data-path^="' + category + '"]').css('display', 'inline-block');
                }
                if (category == '' && tag != '') {
                    $('.azh-elements-form .azh-elements-list .azh-element').css('display', 'none');
                    $('.azh-elements-form .azh-elements-list .azh-element.' + tag).css('display', 'inline-block');
                }
                if (category != '' && tag != '') {
                    $('.azh-elements-form .azh-elements-list .azh-element').css('display', 'inline-block');
                    $('.azh-elements-form .azh-elements-list .azh-element:not([data-path^="' + category + '"])').css('display', 'none');
                    $('.azh-elements-form .azh-elements-list .azh-element:not(.' + tag + ')').css('display', 'none');
                }
            }
        }
        var $form = $('<div class="azh-elements-form azh-modal" title="' + azh.fi18n.elements + '"><div class="azh-modal-title">' + azh.fi18n.elements + '</div></div>');
        var $categories_select = null;
        var $tags_select = null;
        var categories = {};
        categories[azh.fi18n.general] = [];
        if ($('.azh-library .azh-elements .azh-element').length) {
            categories[azh.fi18n.elements] = [];
        }
        var $tabs = $('<div class="azh-tabs"></div>').appendTo($form);
        var $tabs_buttons = $('<div></div>').appendTo($tabs);
        var $tabs_contents = $('<div></div>').appendTo($tabs);
        var ids = {};
        var $general_tab = false;
        for (var category in categories) {
            var id = makeid();
            ids[category] = id;
            $('<span><a href="#' + id + '">' + category + '</a></span>').appendTo($tabs_buttons).on('click', function() {
                //$.modal.update();
            });
        }
        var child_suggestions = [];
        for (var category in categories) {
            var $tab = $('<div id = "' + ids[category] + '"></div>').appendTo($tabs_contents);
            if (category == azh.fi18n.general) {
                $tab.addClass('azh-general-tab');
                $general_tab = $tab;
            }
            for (var i = 0; i < categories[category].length; i++) {
            }
            if (category == azh.fi18n.elements) {
                var filters = $('.azh-library .azh-elements .azh-elements-filters').clone();
                $(filters).appendTo($tab);
                var $elements_list = $('<div class = "azh-elements-list"></div>').appendTo($tab);
                $categories_select = $(filters).find('.azh-categories').on('change', filters_change);
                $tags_select = $('<select class=""></select>').appendTo(filters).on('change', filters_change);
                $('<option selected value="">' + azh.i18n.filter_by_tag + '</option>').appendTo($tags_select);
                for (var tag in azh.tags) {
                    $('<option value="' + tag + '">' + tag + '</option>').appendTo($tags_select);
                }
                $('.azh-library .azh-elements .azh-element').each(function() {
                    var button = $(this).clone();
                    button.appendTo($elements_list).on('click', function() {
                        var button = this;
                        $.get($(button).data('url'), function(data) {
                            data = data.replace(/{{azh-uri}}/g, $(button).data('dir-uri'));
                            data = data.replace(/\[\[([^\]]+)\]\]/g, '');
                            data = data.replace(/\[azh_text\]/g, '');
                            data = data.replace(/\[\/azh_text\]/g, '');
                            data = html_uglify(data);
                            callback($(button).data('path'), data);
                            $.modal.close();
                        });
                    });
                    if ($general_tab && $(button).is('.general')) {
                        var general_button = $(button).clone(true);
                        general_button.appendTo($general_tab);
                    }
                    if (azh.child_suggestions.length) {
                        if (azh.child_suggestions.indexOf(this) >= 0) {
                            child_suggestions.push(button);
                        }
                    }
                });
            }
        }
        if ($general_tab && child_suggestions.length) {
            $(child_suggestions).each(function() {
                var general_button = $(this).clone(true);
                general_button.prependTo($general_tab);
            });
        }
        azh.tabs($tabs);

        var $actions = $('<div class="azh-modal-actions"></div>').appendTo($form);
        $('<div class="azh-modal-cancel">' + azh.fi18n.cancel + '</div>').appendTo($actions).on('click', function() {
            $.modal.close();
            return false;
        });
        $form.modal({
            autoResize: true,
            overlayClose: true,
            opacity: 0,
            overlayCss: {
                "background-color": "black"
            },
            closeClass: "azh-close"
        });
        $tabs.find('> div:first-child > span > a[href^="#"]').on('click', function(event) {
            $.modal.update($('.azh-modal').outerHeight());
        });
    }
    azh.create_shortcode_field = function(settings, value) {
        function set_image(field, url, id) {
            var preview = $(field).find('.azh-image-preview');
            $(preview).empty();
            $('<img src="' + url + '">').appendTo(preview);
            $(preview).data('id', id);
            $(field).trigger('change');
            $('<a href="#" class="remove"></a>').appendTo(preview).on('click', function(event) {
                $(preview).empty();
                $(preview).data('id', '');
                $(field).trigger('change');
                return false;
            });

        }
        function add_images(field, images) {
            var previews = $(field).find('.azh-images-preview');

            for (var i = 0; i < images.length; i++) {
                var preview = $('<div class="azh-image-preview"></div>').appendTo(previews);
                $('<img src="' + images[i]['url'] + '">').appendTo(preview);
                $(preview).data('id', images[i]['id']);
                (function(preview) {
                    $('<a href="#" class="remove"></a>').appendTo(preview).on('click', function(event) {
                        $(preview).remove();
                        $(field).trigger('change');
                        return false;
                    });
                })(preview);
            }

            $(previews).sortable();

            $(field).trigger('change');
        }
        var field = $('<p data-param-name="' + settings['param_name'] + '"></p>');
        $(field).data('settings', settings);
        settings['heading'] = (typeof settings['heading'] == 'undefined' ? '' : settings['heading']);

        if ('dependency' in settings) {
            setTimeout(function() {
                $('[data-param-name="' + settings['dependency']['element'] + '"]').on('change', function() {
                    if ($(this).css('display') == 'none') {
                        $(field).hide();
                        $(field).trigger('change');
                        return;
                    }
                    var value = $(this).data('get_value').call(this);
                    if ('is_empty' in settings['dependency']) {
                        if (value == '') {
                            $(field).show();
                        } else {
                            $(field).hide();
                        }
                    }
                    if ('not_empty' in settings['dependency']) {
                        if (value == '') {
                            $(field).hide();
                        } else {
                            $(field).show();
                        }
                    }
                    if ('value' in settings['dependency']) {
                        var variants = settings['dependency']['value'];
                        if (typeof variants == 'string') {
                            variants = [variants];
                        }
                        if (variants.indexOf(value) >= 0) {
                            $(field).show();
                        } else {
                            $(field).hide();
                        }
                    }
                    if ('value_not_equal_to' in settings['dependency']) {
                        var variants = settings['dependency']['value_not_equal_to'];
                        if (typeof variants == 'string') {
                            variants = [variants];
                        }
                        if (variants.indexOf(value) >= 0) {
                            $(field).hide();
                        } else {
                            $(field).show();
                        }
                    }
                    $(field).trigger('change');
                });
            }, 0);
        }

        switch (settings['type']) {
            case 'textfield':
                $(field).append('<label>' + settings['heading'] + '</label>');
                var textfield = $('<input class="azh-modal-control" type="text">').appendTo(field);
                if (value != '') {
                    $(textfield).val(value);
                } else {
                    $(textfield).val(settings['value']);
                }
                $(textfield).on('change', function() {
                    $(field).trigger('change');
                });
                $(field).data('get_value', function() {
                    return $(this).find('input[type="text"]').val();
                });
                break;
            case 'textarea':
            case 'textarea_html':
            case 'textarea_raw_html':
                $(field).append('<label>' + settings['heading'] + '</label>');
                var textarea = $('<textarea class="azh-modal-control"></textarea>').appendTo(field);
                if (value != '') {
                    $(textarea).val(value);
                } else {
                    $(textarea).val(settings['value']);
                }
                if (settings['type'] == 'textarea_html') {
                    azh.get_rich_text_editor(textarea);
                }
                if (settings['type'] == 'textarea_raw_html') {
                }
                $(textarea).on('change', function() {
                    $(field).trigger('change');
                });
                $(field).data('get_value', function() {
                    return $(this).find('textarea').val();
                });
                break;
            case 'dropdown':
                $(field).append('<label>' + settings['heading'] + '</label>');
                var select = $('<select class="azh-modal-control"></select>');
                if ($.isArray(settings['value'])) {
                    for (var i = 0; i < settings['value'].length; i++) {
                        $(select).append('<option value="' + settings['value'][i][0] + '" ' + (value == settings['value'][i][0] ? 'selected' : '') + '>' + settings['value'][i][1] + '</option>');
                    }
                } else {
                    for (var label in settings['value']) {
                        $(select).append('<option value="' + settings['value'][label] + '" ' + (value == settings['value'][label] ? 'selected' : '') + '>' + label + '</option>');
                    }
                }
                $(select).on('change', function() {
                    $(field).trigger('change');
                });
                $(field).data('get_value', function() {
                    return $(this).find('select option:selected').attr('value');
                });
                $(select).appendTo(field);
                break;
            case 'checkbox':
                var checkbox = $('<fieldset><legend>' + settings['heading'] + '</legend></fieldset>').appendTo(field);
                var values = value.split(',');
                for (var label in settings['value']) {
                    var id = makeid();
                    $(checkbox).append('<input id="' + id + '" type="checkbox" ' + (values.indexOf(settings['value'][label]) >= 0 ? 'checked' : '') + ' value="' + settings['value'][label] + '">');
                    $(checkbox).on('change', function() {
                        $(field).trigger('change');
                    });
                    $(checkbox).append('<label for="' + id + '">' + label + '</label>');
                }
                $(field).data('get_value', function() {
                    var values = $.makeArray($(this).find('input[type="checkbox"]:checked')).map(function(item) {
                        return $(item).attr('value')
                    });
                    return values.join(',');
                });
                break;
            case 'param_group':
                var param_group = $('<fieldset><legend>' + settings['heading'] + '</legend></fieldset>').appendTo(field);
                var table = $('<table></table>').appendTo(param_group);
                var values = JSON.parse(decodeURIComponent(settings['value']));
                if (value != '') {
                    values = JSON.parse(decodeURIComponent(value));
                }
                for (var i = 0; i < values.length; i++) {
                    var row = $('<tr></tr>').appendTo(table);
                    for (var j = 0; j < settings['params'].length; j++) {
                        var column = $('<td></td>');
                        $(column).append(azh.create_shortcode_field(settings['params'][j], (settings['params'][j]['param_name'] in values[i] ? values[i][settings['params'][j]['param_name']] : '')));
                        row.append(column);
                    }
                    $('<a href="#" class="button">' + azh.i18n.remove + '</a>').appendTo($('<td></td>').appendTo(row)).on('click', function() {
                        $(this).closest('tr').remove();
                        return false;
                    });
                }
                $('<a href="#" class="button">' + azh.i18n.add + '</a>').appendTo(param_group).on('click', function() {
                    var row = $('<tr></tr>').appendTo(table);
                    for (var j = 0; j < settings['params'].length; j++) {
                        var column = $('<td></td>');
                        $(column).append(azh.create_shortcode_field(settings['params'][j], ''));
                        row.append(column);
                    }
                    $('<a href="#" class="button">' + azh.i18n.remove + '</a>').appendTo($('<td></td>').appendTo(row)).on('click', function() {
                        $(this).closest('tr').remove();
                        return false;
                    });
                    return false;
                });
                $(field).data('get_value', function() {
                    var values = $.makeArray($(this).find('tr')).map(function(item) {
                        var params = {};
                        $(item).find('[data-param-name]').each(function() {
                            if ($(this).data('get_value')) {
                                params[$(this).data('param-name')] = $(this).data('get_value').call(this);
                            }
                        })
                        return(params);
                    });
                    return encodeURIComponent(JSON.stringify(values));
                });
                break;
            case 'attach_image':
                $(field).append('<label>' + settings['heading'] + '</label>');
                var preview = $('<div class="azh-image-preview"></div>').appendTo(field);
                $('<a href="#" class="button">' + azh.i18n.set + '</a>').appendTo(field).on('click', function(event) {
                    azh.open_image_select_dialog.call(field, event, function(url, id) {
                        set_image(this, url, id);
                    });
                    return false;
                });
                $(field).data('get_value', function() {
                    return $(this).find('.azh-image-preview').data('id');
                });
                if (value != '') {
                    azh.get_image_url(value, function(url) {
                        set_image(field, url, value);
                    });
                }
                break;
            case 'attach_images':
                $(field).append('<label>' + settings['heading'] + '</label>');
                var previews = $('<div class="azh-images-preview"></div>').appendTo(field);
                $('<a href="#" class="button">' + azh.i18n.add + '</a>').appendTo(field).on('click', function(event) {
                    azh.open_image_select_dialog.call(field, event, function(images) {
                        add_images(this, images);
                    }, true);
                    return false;
                });
                $(field).data('get_value', function() {
                    return $.makeArray($(this).find('.azh-images-preview .azh-image-preview')).map(function(item) {
                        return $(item).data('id');
                    }).join(',');
                });
                if (value != '') {
                    var images = value.split(',').map(function(item) {
                        return {id: item};
                    });
                    for (var i = 0; i < images.length; i++) {
                        (function(i) {
                            azh.get_image_url(images[i]['id'], function(url) {
                                images[i]['url'] = url;
                                var all = true;
                                for (var j = 0; j < images.length; j++) {
                                    if (!('url' in images[j])) {
                                        all = false;
                                        break;
                                    }
                                }
                                if (all) {
                                    add_images(field, images);
                                }
                            });
                        })(i);
                    }
                }
                break;
            case 'vc_link':
                $(field).append('<label>' + settings['heading'] + '</label>');
                var wrapper = $('<div class="azh-link-field"></div>').appendTo(field);
                var link = {};
                if (value != '') {
                    value.split('|').map(function(item) {
                        link[item.split(':')[0]] = decodeURIComponent(item.split(':')[1]);
                    });
                }
                $(field).data('link', link);
                var button = $('<a href="#" class="button">' + azh.i18n.select_url + '</a>').appendTo(wrapper).on('click', function(event) {
                    var link = $(field).data('link');
                    azh.open_link_select_dialog.call(this, event, function(url, target, title) {
                        var link = {
                            url: url,
                            target: target,
                            title: title,
                            rel: 'nofollow'
                        };
                        $(field).data('link', link);
                        $(title_span).text(title);
                        $(url_span).text(url);
                    }, ('url' in link ? link['url'] : ''), ('target' in link ? link['target'] : ''), ('title' in link ? link['title'] : ''));
                    return false;
                });
                $(wrapper).append('<label>' + azh.i18n.title + '</label>');
                var title_span = $('<span>' + ('title' in link ? link['title'] : '') + '</span>').appendTo(wrapper);
                $(wrapper).append('<label>' + azh.i18n.url + '</label>');
                var url_span = $('<span>' + ('url' in link ? link['url'] : '') + '</span>').appendTo(wrapper);
                $(field).data('get_value', function() {
                    return $.map($(this).data('link'), function(value, index) {
                        return [index + ':' + encodeURIComponent(value)];
                    }).join('|');
                });
                break;
            case 'iconpicker':
                $(field).append('<label>' + settings['heading'] + '</label>');
                var textfield = $('<input class="azh-modal-control" type="text">').appendTo(field);
                $(textfield).val(value);
                azh.icon_select_dialog(function(icon) {
                    $(textfield).val(icon);
                }, settings['settings']['type']).appendTo(field);
                $(field).data('get_value', function() {
                    return  $(this).find('input[type="text"]').val();
                });
                break;
            case 'autocomplete':
                $(field).append('<label>' + settings['heading'] + '</label>');
                var textfield = $('<input class="azh-modal-control" type="text">').appendTo(field);
                $(field).data('value', value);
                var shortcode_settings = {};
                setTimeout(function() {
                    shortcode_settings = $(field).closest('.azh-from').data('settings');
                    if ($.trim(value) != '') {
                        $.post(ajaxurl, {
                            'action': 'azh_autocomplete_labels',
                            'shortcode': shortcode_settings['base'],
                            'param_name': settings['param_name'],
                            'values': value
                        }, function(data) {
                            $(textfield).val(Object.keys(data).map(function(item) {
                                return data[item]
                            }).join(', '));
                            $(field).data('value', Object.keys(data).join(','));
                        }, 'json');
                    }
                });

                $(textfield).on("keydown", function(event) {
                    if (event.keyCode === $.ui.keyCode.TAB && $(this).autocomplete("instance").menu.active) {
                        event.preventDefault();
                    }
                }).autocomplete({
                    minLength: 0,
                    source: function(request, response) {
                        if (request.term.split(/,\s*/).pop() != '') {
                            $.post(ajaxurl, {
                                'action': 'azh_autocomplete',
                                'shortcode': shortcode_settings['base'],
                                'param_name': settings['param_name'],
                                'exclude': $(field).data('value'),
                                'search': request.term.split(/,\s*/).pop()
                            }, function(data) {
                                response(data);
                            }, 'json');
                        } else {
                            response();
                        }
                    },
                    focus: function(event, ui) {
                        return false;
                    },
                    select: function(event, ui) {
                        if (ui.item) {
                            var labels = this.value.split(/,\s*/);
                            labels.pop();
                            labels.push(ui.item.label);
                            labels.push('');
                            this.value = labels.join(', ');

                            var values = $(field).data('value').split(/,\s*/);
                            values.push(ui.item.value);
                            $(field).data('value', values.join(',').replace(/,\s*$/, '').replace(/^\s*,/, ''));

                        }
                        return false;
                    }
                }).on("keydown keyup blur", function(event) {
                    if ($(textfield).val() == '') {
                        $(field).data('value', '');
                    }
                });
                $(textfield).autocomplete('instance')._create = function() {
                    this._super();
                    this.widget().menu('option', 'items', '> :not(.ui-autocomplete-group)');
                };
                $(textfield).autocomplete('instance')._renderMenu = function(ul, items) {
                    var that = this, currentGroup = '';
                    $.each(items, function(index, item) {
                        var li;
                        if ('group' in item && item.group != currentGroup) {
                            ul.append('<div class="ui-autocomplete-group">' + item.group + '</div>');
                            currentGroup = item.group;
                        }
                        li = that._renderItemData(ul, item);
                        if ('group' in item && item.group) {
                            li.attr('aria-label', item.group + ' : ' + item.label);
                        }
                    });
                };
                $(field).data('get_value', function() {
                    return  $(this).data('value');
                });
                break;
        }
        if ($(field).data('get_value')) {
            if ('description' in settings) {
                $(field).append('<em>' + settings['description'] + '</em>');
            }
        }
        return field;
    }
    azh.create_shortcode_form = function(settings, values) {
        if ('params' in settings) {
            var form = $('<div class="azh-from" title="' + settings['name'] + '"></div>');
            $(form).data('settings', settings);
            var groups = {'General': []};
            for (var i = 0; i < settings['params'].length; i++) {
                if ('group' in settings['params'][i]) {
                    groups[settings['params'][i]['group']] = [];
                }
            }
            for (var i = 0; i < settings['params'].length; i++) {
                if ('group' in settings['params'][i]) {
                    groups[settings['params'][i]['group']].push(settings['params'][i]);
                } else {
                    groups['General'].push(settings['params'][i]);
                }
            }
            var tabs = $('<div class="azh-tabs"></div>').appendTo(form);
            var tabs_buttons = $('<div></div>').appendTo(tabs);
            var ids = {};
            for (var group in groups) {
                var id = makeid();
                ids[group] = id;
                $('<span><a href="#' + id + '">' + group + '</a></span>').appendTo(tabs_buttons);
            }
            var tabs_content = $('<div></div>').appendTo(tabs);
            for (var group in groups) {
                var tab = $('<div id = "' + ids[group] + '"></div>').appendTo(tabs_content);
                for (var i = 0; i < groups[group].length; i++) {
                    azh.create_shortcode_field(groups[group][i], (groups[group][i]['param_name'] in values ? values[groups[group][i]['param_name']] : '')).appendTo(tab);
                }
            }
            azh.tabs(tabs);
            setTimeout(function() {
                $(form).find('[data-param-name]').trigger('change');
            }, 0);
            return form;
        }
    }
    $.QueryString = azh.parse_query_string(window.location.search.substr(1).split('&'));
    if ('azh' in $.QueryString && $.QueryString['azh'] == 'customize') {
        on_ready_first(function() {
            $body = $('body');
            azh.store_html($body.get(0));
        });
        $(function() {
            createEditor();
            $window.one('az-frontend-before-init', function(event, data) {
                var $wrapper = data.wrapper;
                if (azh.content_wrapper.has($wrapper).length) {
                    $wrapper.find('[data-section]').andSelf().filter('[data-section]').each(function() {
                        azh.section_customization_init($(this));
                    });
                } else {
                    if ($wrapper.is($body)) {
                        azh.content_wrapper.find('[data-section]').andSelf().filter('[data-section]').each(function() {
                            azh.section_customization_init($(this));
                        });
                    }
                }
            });
            $window.one('az-frontend-after-init', function(event, data) {
                azh.refresh_controls(azh.content_wrapper);
            });
            $window.on('click', function(event, data) {
                azh.refresh_controls(azh.content_wrapper);
            });
            $window.on("az-full-width", function() {
                $('.azh-control').trigger('azh-init');
            });
            if ($body.is('.page-template-azexo-html-template')) {
                azh.content_wrapper = $body.find('> .page');
                azh.structure_refresh(azh.content_wrapper);
                azh.library_init(azh.content_wrapper);
            } else {
                if ($body.is('.page')) {
                    if ('azexo' in window) {
                        azh.content_wrapper = $body.find('#content > .entry > .entry-content');
                    } else {
                        azh.content_wrapper = $body.find('[data-section]').parent();
                    }
                    azh.structure_refresh(azh.content_wrapper);
                    azh.library_init(azh.content_wrapper);
                }
            }
        });
    } else {
        $(function() {
            azh.edit_links_refresh = function() {
                function show_edit_link($element) {
                    $($element.data('edit-link-control')).css({
                        "top": $element.offset().top,
                        "left": $element.offset().left,
                        "width": $element.outerWidth(),
                        "height": $element.outerHeight()
                    }).show();
                }
                function hide_edit_link($element) {
                    $($element.data('edit-link-control')).hide();
                }
                function is_visible($element) {
                    var visible = true;
                    if ($(window).width() < $element.offset().left + $element.outerWidth()) {
                        visible = false;
                    }
                    if (!$element.is(":visible")) {
                        visible = false;
                    }
                    $element.parents().each(function() {
                        var $parent = $(this);

                        var elements = $parent.data('elements-with-azh-edit-link');
                        if (!elements) {
                            elements = [];
                        }
                        elements = elements.concat($element.get());
                        elements = $.unique(elements);
                        $parent.data('elements-with-azh-edit-link', elements);

                        if ($parent.css("display") == 'none' || $parent.css("opacity") == '0' || $parent.css("visibility") == 'hidden') {
                            visible = false;
                            $parent.off('click.azh mouseenter.azh mouseleave.azh').on('click.azh mouseenter.azh mouseleave.azh', function() {
                                var elements = $parent.data('elements-with-azh-edit-link');
                                $(elements).each(function() {
                                    if (is_visible($(this))) {
                                        show_edit_link($(this));
                                    } else {
                                        hide_edit_link($(this));
                                    }
                                });
                            });
                        }
                    });
                    return visible;
                }
                for (var links_type in azh.edit_links) {
                    var selectors = Object.keys(azh.edit_links[links_type].links);
                    selectors.sort(function(a, b) {
                        return b.length - a.length;
                    });
                    for (var i = 0; i < selectors.length; i++) {
                        var selector = selectors[i];
                        var occurrence = 0;
                        $(selector).each(function() {
                            if (!$(this).data('edit-link-control')) {
                                var control = $('<div class="azh-edit-link" data-edit-link-control=""><a href="' + azh.edit_links[links_type].links[selector] + '&occurrence=' + occurrence + '" target="' + azh.edit_links[links_type].target + '">' + azh.edit_links[links_type].text + '</a></div>').appendTo('body').css({
                                    "top": "0",
                                    "left": "0",
                                    "width": "0",
                                    "height": "0",
                                    "z-index": "9999999",
                                    "pointer-events": "none",
                                    "position": "absolute"
                                }).hide();
                                occurrence++;
                                control.find('a').css({
                                    "position": "absolute",
                                    "display": "inline-block",
                                    "padding": "5px 10px",
                                    "color": "black",
                                    "font-weight": "bold",
                                    "background-color": "white",
                                    "box-shadow": "0px 5px 5px rgba(0, 0, 0, 0.1)",
                                    "pointer-events": "all"
                                }).on('mouseenter', function() {
                                    $(this).parent().css("background-color", "rgba(0, 255, 0, 0.1)");
                                    azh.edit_links_refresh();
                                }).on('mouseleave', function() {
                                    $(this).parent().css("background-color", "transparent");
                                });
                                if ('css' in azh.edit_links[links_type]) {
                                    $(control).find('a').css(azh.edit_links[links_type].css);
                                }
                                $(this).data('edit-link-control', control);
                                $(control).data('azh-linked-element', this);
                            }
                            if (is_visible($(this))) {
                                show_edit_link($(this));
                            } else {
                                hide_edit_link($(this));
                            }
                        });
                    }
                }
            };
            if ('azh' in window && 'edit_links' in azh) {
                $(window).on('resize.azh scroll.azh', _.throttle(function() {
                    azh.edit_links_refresh();
                }, 1000));
                setTimeout(function() {
                    azh.edit_links_refresh();
                }, 100);
            }
            $('#wp-admin-bar-edit-links').addClass('azh-edit-links-active');
            $('#wp-admin-bar-edit-links').off('click.azh').on('click.azh', function(event) {
                var $this = $(this);
                if ($this.is('.azh-edit-links-active')) {
                    $('body > div.azh-edit-link[style] > a[href][style][target]').each(function() {
                        var $this = $(this);
                        if ($this.is(':visible')) {
                            $this.data('visible', true);
                            $this.hide();
                        }
                    });
                    $('body > div.azh-edit-link[style] > a[href][style][target]').hide();
                    $this.removeClass('azh-edit-links-active');
                    $this.css('opacity', '0.4');

                    $(window).off('resize.azh scroll.azh');
                } else {
                    $('body > div.azh-edit-link[style] > a[href][style][target]').each(function() {
                        var $this = $(this);
                        if ($this.data('visible')) {
                            $this.show();
                        }
                    });
                    $this.addClass('azh-edit-links-active');
                    $this.css('opacity', '1');
                    $(window).on('resize.azh scroll.azh', function() {
                        azh.edit_links_refresh();
                    });
                }
                event.preventDefault();
            });
        });
    }
})(window.jQuery);