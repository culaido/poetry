/*
 * jQuery Uploader Plugin 1.1.0
 *
 * dependence:
 *   1. [bootstrap 2.3.2](https://github.com/blueimp/jQuery-File-Upload)
 *   2. [jQuery 2.0.3](https://github.com/jquery/jquery)
 *   3. [jQuery File Upload Plugin](https://github.com/blueimp/jQuery-File-Upload)
 *   4. [jQuery ScrollTo Plugin](http://flesler.blogspot.com)
 *   5. [jQuery IFrame Transport Plugin](https://github.com/blueimp/jQuery-File-Upload) (IE9 Only)
 *
 * browser support:
 *   * IE 9+
 *   * Other: newest version
 *
 */
! function(window, $) {
    "use strict"; // jshint ;_;

    /* UPLOADER CLASS DEFINITION
     * ====================== */
    var Uploader = function(options) {
        this.options = options;
        this.$list = $(this.options.container);
        this.item = {}; // struct of items
        this.choosable = false;

        // modal
        this.modal = '\
        <div class="modal modal-uploader hide fade" tabindex="-1" data-role="uploader" aria-labelledby="uploadBoxLabel" aria-hidden="true">\
            <div class="modal-header text-center">\
                <button type="button" class="close" data-dismiss="uploader" aria-hidden="true">&times;</button>\
                <span data-role="uploader-hint">' + this.options.lang.dropHint + '</span>\
            </div>\
            <div class="modal-body">\
                <div class="row-fluid">\
                    <div class="span12">\
                        <ul data-role="uploader-item" class="thumbnails">\
                        </ul>\
                    </div>\
                </div>\
            </div>\
            <div class="modal-footer clearfix">\
                <button class="btn btn-small btn-dismiss" data-role="closeModalBtn" data-dismiss="uploader">' + this.options.lang.close + '</button>\
                <div class="text-center pull-left">\
                    <span class="btn btn-small btn-primary btn-uploader pull-left">\
                        <span>' + this.options.lang.upload + '</span>\
                        <input data-role="uploader-file" type="file" name="files[]" multiple directory>\
                    </span>\
                </div>\
                <div data-role="uploader-progress" class="progress progress-success progress-striped active">\
                    <div class="bar"></div>\
                </div>\
            </div>\
        </div>';
        $(this.modal).on('click.dismiss.uploader', '[data-dismiss="uploader"]', $.proxy(this.hide, this)).appendTo("body");
        this.$element = $(".modal-uploader[data-role='uploader']").data('uploader', this);


        // feature detect
        this.initUpload();
        this.featureDetect();

        // pre-data
        if (this.options) {
            this.addItem(this.options.data);
        }

        // evnet binding
        var that = this;

        that.$element.on('click.choose.uploader', ".item[data-role='file']", function(e) {
            if (!that.choosable) {
                $(this).find(".tool").trigger("click.preview.uploader");
                return false;
            }

            var data = that.item[$(this).attr('data-id')];

            // upload mode
            if ($(this).attr('data-url') !== undefined) {
                window.open($(this).attr('data-url'));
                return false;
            }

            var validResult = that.valid(data, that.limit);
            if (validResult !== true) {
                alert(validResult);
                return false;
            }

            if (that.callback) {
                that.callback(data);
            }

            that.activeTarget.trigger({
                'type': 'choosed',
                'ret': data
            });

            that.hide();
        });

        that.$element.on('click.delete.uploader', '[data-role="delete"]', function(e) {
            var target = $(this).closest('.item');
            that.deleteItem(target);
            return false;
        });

        that.$list.on('click.delete.uploader', '[data-role="delete"]', function(e) {
            var target = $(this).closest('.item');
            that.deleteItem(target);
            return false;
        });

    }

    Uploader.prototype = {

        constructor: Uploader,

        toggle: function() {
            return this[!this.isShown ? 'show' : 'hide']();
        },

        show: function() {
            var that = this,
                e = $.Event('show');

            this.$element.trigger(e);

            if (this.isShown || e.isDefaultPrevented()) return;

            this.isShown = true;

            this.escape();

            this.backdrop(function() {
                var transition = $.support.transition && that.$element.hasClass('fade');

                if (!that.$element.parent().length) {
                    that.$element.appendTo(document.body); //don't move modals dom position
                }

                that.$element.show();

                if (transition) {
                    that.$element[0].offsetWidth // force reflow
                }

                that.$element.addClass('in').attr('aria-hidden', false);

                that.enforceFocus();

                transition ? that.$element.one($.support.transition.end, function() {
                    that.$element.focus().trigger('shown')
                }) : that.$element.focus().trigger('shown');

            });

            this.repositionPreview();
        },

        hide: function(e) {
            if (e instanceof Array) {
                e = e[0];
            }
            e && e.preventDefault();

            var that = this;

            that.activeTarget = null;
            that.callback = null;

            e = $.Event('hide');

            this.$element.trigger(e)

            if (!this.isShown || e.isDefaultPrevented()) return;

            this.isShown = false;

            this.escape();

            $(document).off('focusin.uploader');

            this.$element.removeClass('in').attr('aria-hidden', true);

            $.support.transition && this.$element.hasClass('fade') ? this.hideWithTransition() : this.hideuploader();
        },

        // item [limit], [callback]
        choose: function(args) {
            var that = this;
            that.show();
            that.activeTarget = $(args[0]);
            if (typeof args[1] === 'function') {
                that.callback = args[1];
                return;
            } else if (typeof args[1] === 'object') {
                that.limit = args[1];
                if (typeof args[2] === 'function') {
                    that.callback = args[2];
                }
                return;
            }
        },

        switchMode: function(args) {

            var mode = args ? args[0] : undefined;

            var that = this;


            if (mode !== 'browse') {
                that.choosable = true;
                that.$element.find('[data-role="uploader-choose"]').show();
            } else {
                that.choosable = false;
                that.$element.find('[data-role="uploader-choose"]').hide();
            }

            if (typeof that.options.upload[mode] === 'undefined') {
                var first;
                for (first in that.options.upload) break;
                mode = first;
            }

            if (!mode) return;

            that.mode = mode;
            var uploadOption = that.options.upload[mode],
                accept = uploadOption.mimeType;

            that.$element.find(".item[data-type='attach']").toggleClass('in', mode === 'attach');

            delete(uploadOption.mimeType);

            that.$element.find("[data-role='uploader-file']").attr('accept', accept).fileupload(
                'option', uploadOption
            );
        },


        /* ===================================
         * NON-PUBLIC FUNCTION BELOW THIS LINE
         * =================================== */
        enableDragAnimation: function() {
            var that = this;
            $(document).unbind('dragover.upload.uploader').bind('dragover.upload.uploader', function(e) {
                var dropZone = that.$element,
                    timeout = window.uploaderTimeout;

                if (!timeout) {
                    dropZone.addClass('drag');
                } else {
                    clearTimeout(timeout);
                }

                if (e.target == dropZone[0] || $(e.target).closest(".modal-uploader[data-role='uploader']").length > 0) {
                    dropZone.addClass('draghover');
                } else {
                    dropZone.removeClass('draghover');
                }

                window.uploaderTimeout = setTimeout(function() {
                    window.uploaderTimeout = null;
                    dropZone.removeClass('drag draghover');
                }, 100);
            });
        },

        disableDragAnimation: function() {
            $(document).unbind('dragover.upload.uploader');
            window.uploaderTimeout = null;
        },

        enablePreview: function() {
            var that = this;
            if (that.$element.find('.extra[data-role="uploader-extra"]').length) {
                return;
            }
            var previewBox = '<li class="extra clearfix" data-role="uploader-extra">\
                                <div class="row-fluid">\
                                    <div class="span8 preview">\
                                        <div class="preview-body">\
                                        </div>\
                                    </div>\
                                    <div class="span4 detail">\
                                        <ul class="info">\
                                            <li>' + that.options.lang.filename + ': <span data-placement="filename"></sapn></li>\
                                            <li>' + that.options.lang.filesize + ': <span data-placement="size"></sapn></li>\
                                            <li>' + that.options.lang.imgsize + ': <span data-placement="imgSize"></sapn></li>\
                                            <li>' + that.options.lang.mimetype + ': <span data-placement="mimeType"></sapn></li>\
                                            <li><a data-role="uploader-oriFile">' + that.options.lang.oriFile + '</a></li>\
                                        </ul>\
                                        <div class="action">\
                                            <button data-role="uploader-choose" class="btn btn-small btn-success">' + that.options.lang.choose + '</button>\
                                            <button data-role="uploader-delete" class="btn btn-small btn-danger">' + that.options.lang.delete + '</button>\
                                        </div>\
                                    </div>\
                                </div>\
                            </li>';

            that.$element.find('[data-role="uploader-item"]').append(previewBox);

            var preview = that.$element.find('.extra[data-role="uploader-extra"]'),
                chooseEle = preview.find("[data-role='uploader-choose']"),
                oriFileEle = preview.find("[data-role='uploader-oriFile']"),
                deleteEle = preview.find("[data-role='uploader-delete']");

            // event: toggle preview
            that.$element.on('click.preview.uploader', ".item .tool", function(e) {
                var item = $(this).closest('.item'),
                    features = ['filename', 'size', 'imgSize', 'mimeType'];

                for (var k in features) {
                    var itemData = that.item[item.attr('data-id')],
                        target = preview.find("[data-placement='" + features[k] + "']");

                    if (features[k] === 'size') { // byte format
                        itemData[features[k]] = that.byteFormat(itemData[features[k]]);
                    }

                    if (features[k] === 'mimeType') { // mimeType
                        var downloadUrl = (itemData[features[k]].indexOf('image') === 0) ? itemData['downloadUrl'] : itemData['thumbUrl'];

                        preview.find('.preview-body').html("<img src='" + downloadUrl + "'/>");
                        oriFileEle.attr({
                            href: that.item[item.attr('data-id')]['cdnDownloadUrl'],
                            target: '_blank'
                        });
                    }

                    target.html(itemData[features[k]]).attr('title', itemData[features[k]]);
                    (itemData[features[k]] === undefined || itemData[features[k]] === '') ? target.closest('li').hide() : target.closest('li').show();
                }

                chooseEle.attr('data-target', item.attr('data-id'));
                deleteEle.attr('data-target', item.attr('data-id'));

                that.repositionPreview(item);
                return false;
            });

            // event: delete
            deleteEle.bind('click.delete.preview.uploader', function(e) {
                that.$element.find("[data-id='" + $(this).attr('data-target') + "'] [data-role='delete']").trigger('click.delete.uploader');
                that.repositionPreview($(this).closest('.item'));
            });

            // event: choose
            chooseEle.bind('click.choose.preview.uploader', function(e) {
                that.$element.find("[data-id='" + $(this).attr('data-target') + "']").trigger('click.choose.uploader');
            });
        },

        disablePreview: function() {
            var that = this;
            that.$element.find('.extra[data-role="uploader-extra"]').remove();
        },

        enableMini: function() {
            var that = this;
            that.$element.addClass('modal-uploader-mini');
        },

        disableMini: function() {
            var that = this;
            that.$element.removeClass('modal-uploader-mini');
        },

        deleteItem: function(item) {
            var that = this;
            if (confirm(that.options.lang.cfmDel)) {
                $.post(that.item[$(item).attr('data-id')]['deleteUrl'], function(obj) {
                    if (obj.ret.msg === 'false') {
                        alert(obj.ret.msg);
                        return;
                    }
                    $(item).hide('fast', function() {
                        delete(that.item[item.attr('data-id')]);
                        that.$element.find("[data-id='"+item.attr('data-id')+"']").remove();
                        that.renderList();
                    });
                    that.repositionPreview();
                }, 'json');
            }
        },

        // [{id, filename, name, ext, size, mimeType, imgSize, thumbUrl, cdnDownloadUrl, downloadUrl, deleteUrl, createTime}]
        addItem: function(data) {
            if (!data) return;

            var that = this,
                pool = [];
            $.each(data, function(idx, item) {

                var dataUrl = (that.mode === 'attach') ? "data-type='attach'" : "data-type='upload' data-url='" + item.url + "'";
                var ele = "\
                    <li class='item in' data-role='file' data-id='" + item.id + "' " + dataUrl + ">\
                        <a class='thumbnail'>\
                            <div class='tool'>\
                                <i class='icon-white icon-search pull-right'></i>\
                                <div class='filename' title='" + item.filename + "'>\
                                    <div class='name'>" + item.name + "</div>\
                                    <div class='ext'>" + item.ext + "</div>\
                                </div>\
                            </div>\
                            <img src='" + that.options.img.delete + "' class='cross' data-role='delete'>\
                            <img src='" + item.thumbUrl + "' alt='" + item.filename + "'>\
                        </a>\
                    </li>";
                pool.push(ele);
                that.item[item.id] = item; // struct of items
            });
            that.$element.find('[data-role="uploader-item"]').append(pool);
            that.repositionPreview();
            that.renderList();
        },

        renderList: function() {
            var that = this,
                pool = [];

            $.each(that.item, function(idx, item) {
                pool.push("<li class='item' data-id='" + item.id + "' >\
                                <a href=" + item.cdnDownloadUrl + " target='_blank'>" + item.filename + "</a>\
                                <i class='icon-remove' data-role='delete' title='" + that.options.lang.delete + "'></i>\
                                <span class='hint'>(" + that.byteFormat(item.size) + ")</span>\
                            </li>");
            });
            var box = $("<ol class='uploader-list'></ol>").append(pool);
            $(that.options.container).empty().append(box);
        },
        countItem: function() {
            return Object.keys(this.item).length;
        },

        // default.limit: {mimeType: undefined, maxHeight: 0, minHeight: 0, maxWidth: 0, minWidth: 0 }
        valid: function(data, limit) {
            var that = this,
                errorMsg = [];

            // return if no limit
            if (!limit) {
                return true;
            }

            // mimeType
            if (limit['mimeType'] && data['mimeType'] !== '*' && data['mimeType'].indexOf(limit['mimeType']) === -1) {
                errorMsg.push(that.options.lang.mimeTypeNotMatch);
            }

            // image size
            if (data['mimeType'] === '*' || data['mimeType'].indexOf("image") === 0) { // height or width limit
                var dim = data['imgSize'].split("x");
                if (limit['minWidth'] && limit['minWidth'] > dim[0]) {
                    errorMsg.push(that.options.lang.imgWidthTooSmall + " " + limit['minWidth'] + " " + that.options.lang.pixel);
                }
                if (limit['minHeight'] && limit['minHeight'] > dim[1]) {
                    errorMsg.push(that.options.lang.imgHeightTooSmall + " " + limit['minHeight'] + " " + that.options.lang.pixel);
                }
                if (limit['maxWidth'] && limit['maxWidth'] < dim[0]) {
                    errorMsg.push(that.options.lang.imgWidthTooLarge + " " + limit['maxWidth'] + " " + that.options.lang.pixel);
                }
                if (limit['maxHeight'] && limit['maxHeight'] < dim[1]) {
                    errorMsg.push(that.options.lang.imgHeightTooLarge + " " + limit['maxHeight'] + " " + that.options.lang.pixel);
                }
            }

            return errorMsg.length ? errorMsg.join("\n") : true;
        },

        initUpload: function() {
            var that = this,
                uploadEle = that.$element.find("[data-role='uploader-file']"),
                progressEle = that.$element.find('.progress[data-role="uploader-progress"]'),
                closeEle = that.$element.find("button[data-role='closeModalBtn']");

            uploadEle.fileupload({
                lang: {
                    error: {
                        'errorExt': that.options.lang.errorExt,
                        'sizeTooLarge': that.options.lang.sizeTooLarge
                    }
                },
                messages: that.options.messages,
                dataType: 'json',
                dropZone: that.$element,
                start: function(e) {
                    $(window).bind('beforeunload.upload.uploader', function() {
                        return that.options.lang.leavePS;
                    });
                },
                done: function(e, data) {
                    var pool = [];
                    $.each(data.result.files, function(index, file) {

                        if (typeof file.error !== 'undefined') {
                            alert(file.error);
                            return;
                        }

                        var nameArray = that.parseFileName(file.oriFileName);
                        pool.push({
                            id: file.id,
                            filename: file.oriFileName,
                            url: file.url,
                            name: nameArray[0],
                            ext: nameArray[1].length > 0 ? "." + nameArray[1] : "",
                            size: file.size,
                            mimeType: file.type,
                            imgSize: file.imgSize,
                            thumbUrl: file.thumbnail_url,
                            deleteUrl: file.delAuth,
                            downloadUrl: file.url,
                            cdnDownloadUrl: file.cdn_url,
                            createTime: file.createTime
                        });

                        that.$element.focus().trigger('file-uploaded', file);

                    });
                    that.addItem(pool);
                    that.repositionPreview();
                },
                progressall: function(e, data) {
                    var progress = parseInt(data.loaded / data.total * 100, 10);

                    progressEle.find('.bar').css('width', progress + '%');

                    if (progress === 100) {
                        $(window).unbind('beforeunload.upload.uploader');
                        progressEle.toggleClass('active', false).removeClass('progress-danger');
                        closeEle.html(that.options.lang.close).prop('disabled', false);
                    } else {
                        progressEle.toggleClass('active', true);
                        closeEle.html(that.options.lang.uploading).prop('disabled', true).addClass('btn-success');
                    }

                },
                getNumberOfFiles: function() {
                    return that.countItem();
                }
            }).bind('fileuploadfail', function(e, data) {
                progressEle.removeClass("progress-success active").addClass('progress-danger');
                closeEle.html(that.options.lang.close).prop('disabled', false).removeClass("btn-success");
                $(window).unbind('beforeunload.upload.uploader');
                console.log(data);
                console.log(e);
                alert(data.errorThrown);
            }).bind('fileuploadprocessfail', function(e, data) {
                alert(data.files[0].error);
            });
            that.switchMode();
        },

        featureDetect: function() {
            var that = this;

            // Remove drop-here description if DnD is not support
            if (!('draggable' in document.createElement('span'))) {
                $(document).unbind('dragover.upload.uploader');
                that.$element.find("span[data-role='uploader-hint']").html("&nbsp;");
            }

            // return if support XHR
            if ( !! (window.XMLHttpRequest && window.FileReader)) return;

            $.getScript(that.options.script.iframeTransport, function(data, textStatus, jqxhr) {
                that.$element.find('[data-role="uploader-file"]').fileupload(
                    'option', {
                        forceIframeTransport: true,
                        limitConcurrentUploads: undefined,
                        maxChunkSize: undefined
                    }
                ).removeAttr('multiple');
            });
        },

        repositionPreview: function(item) {
            var that = this,
                preview = that.$element.find('.extra[data-role="uploader-extra"]'),
                container = that.$element.find('.thumbnails[data-role="uploader-item"]');

            if (preview.length === 0) {
                return;
            }

            if (item && preview.attr('data-target') === item.attr('data-id')) {
                preview.toggleClass('in');
                return;
            }

            if (item === undefined) {
                preview.appendTo(container).removeClass('in').appendTo(container);
            } else {
                var preLen = item.prevAll(".item.in").length,
                    nextLen = item.nextAll(".item.in").length,
                    colOfRow = that.$element.hasClass('modal-uploader-mini') ? 3 : 6;

                if (preLen % colOfRow === colOfRow - 1) {
                    preview.insertAfter(item).addClass('in');
                } else {
                    var endOfLine = colOfRow - (preLen % colOfRow) - 1;
                    if (nextLen > endOfLine) {
                        preview.insertAfter(that.$element.find('.item.in').eq(preLen + endOfLine)).addClass('in');
                    } else {
                        preview.appendTo(container).addClass('in');
                    }
                }
                preview.attr('data-target', item.attr('data-id'));
                $(".thumbnails[data-role='uploader-item']").scrollTo(item);
            }

        },

        parseFileName: function(fileName) {
            var unit = fileName.split(".");
            var ext = (unit.length === 1 || (unit[0] === "" && unit.length === 2)) ? '' : unit.pop();
            var name = unit.join(".");
            return [name, ext];
        },

        byteFormat: function(size, precision) {
            var _size = parseInt(size, 10),
                prec = precision || 2,
                levelIndex = 0;
            while (_size > 1024) {
                _size /= 1024.0;
                levelIndex++;
            }
            return parseFloat(_size.toFixed(prec)) + " " + ['bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB'][levelIndex];
        },

        enforceFocus: function() {
            var that = this;
            $(document).on('focusin.uploader', function(e) {
                if (that.$element[0] !== e.target && !that.$element.has(e.target).length) {
                    that.$element.focus();
                }
            });
        },

        escape: function() {
            var that = this
            if (this.isShown && this.options.keyboard) {
                this.$element.on('keyup.dismiss.uploader', function(e) {
                    e.which == 27 && that.hide();
                })
            } else if (!this.isShown) {
                this.$element.off('keyup.dismiss.uploader')
            }
        },

        hideWithTransition: function() {
            var that = this,
                timeout = setTimeout(function() {
                    that.$element.off($.support.transition.end);
                    that.hideuploader();
                }, 500)

                this.$element.one($.support.transition.end, function() {
                    clearTimeout(timeout);
                    that.hideuploader();
                })
        },

        hideuploader: function() {
            this.$element.hide().trigger('hidden');
            this.backdrop();
        },

        removeBackdrop: function() {
            this.$backdrop.remove();
            this.$backdrop = null;
        },

        backdrop: function(callback) {
            var that = this,
                animate = this.$element.hasClass('fade') ? 'fade' : ''

            if (this.isShown && this.options.backdrop) {
                var doAnimate = $.support.transition && animate;

                this.$backdrop = $('<div class="modal-backdrop ' + animate + '" />').appendTo(document.body)

                this.$backdrop.click(
                    this.options.backdrop == 'static' ? $.proxy(this.$element[0].focus, this.$element[0]) : $.proxy(this.hide, this)
                );

                if (doAnimate) this.$backdrop[0].offsetWidth // force reflow

                this.$backdrop.addClass('in');

                doAnimate ? this.$backdrop.one($.support.transition.end, callback) : callback();

            } else if (!this.isShown && this.$backdrop) {
                this.$backdrop.removeClass('in');

                $.support.transition && this.$element.hasClass('fade') ? this.$backdrop.one($.support.transition.end, $.proxy(this.removeBackdrop, this)) : this.removeBackdrop();

            } else if (callback) {
                callback();
            }
        }
    }


    /* UPLOADER PLUGIN DEFINITION
     * ======================= */

    var old = $.uploader;

    $.uploader = function(option) {

        var $modal = $(".modal-uploader[data-role='uploader']"),
            data = $modal.data('uploader'),
            options = $.extend(true, {}, $.uploader.defaults, $modal.data(), typeof option == 'object' && option);

        if (!data) {
            $modal.data('uploader', (data = new Uploader(options)));
        } else {
            $.extend($modal.data('uploader').options, option);
        }

        if (typeof option == 'string') {
            data[option](Array.prototype.slice.call(arguments, 1));
        } else {
            if (options.show) {
                data.show();
            }
            (options.dragAnimation) ?
                data.enableDragAnimation() :
                data.disableDragAnimation();

            (options.preivew) ?
                data.enablePreview() :
                data.disablePreview();

            (options.mini) ?
                data.enableMini() :
                data.disableMini();

        }
    }

    $.uploader.defaults = {
        backdrop: true,
        keyboard: true,
        show: false,

        mini: false,
        dragAnimation: true,
        preivew: true,
        mode: 'attach',

        lang: {
            'dropHint': 'Drop here',
            'delete': 'Delete',
            'close': 'Close',
            'choose': 'Choose',
            'upload': 'Upload',
            'filename': 'Filename',
            'filesize': 'Filesize',
            'imgsize': 'Imgsize',
            'mimetype': 'Mimetype',
            'oriFile': 'Original file',
            'uploading': 'Uploading',

            'cfmDel': 'Do you really want to delete this file?',
            'leavePS': 'File is uploading, exit this page anyway?',

            'mimeTypeNotMatch': 'MimeType dismatch',
            'imgWidthTooSmall': 'Image width should bigger than',
            'imgWidthTooLarge': 'Image width should smaller than',
            'imgHeightTooSmall': 'Image height should bigger than',
            'imgHeightTooLarge': 'Image height should smaller than',
            'pixel': 'pixel'
        },
        messages: {
            'maxNumberOfFiles': 'Maximum number of files exceeded',
            'acceptFileTypes': 'File type not allowed',
            'maxFileSize': 'File is too large',
            'minFileSize': 'File is too small'
        },
        img: {
            'delete': '/sys/res/icon/cross-black.png'
        },
        script: {
            iframeTransport: '/sys/js/jquery.iframe-transport.js'
        },
        container: undefined,
        data: [],
        upload: {},
        choose: {
            mimeType: undefined,
            maxHeight: 0,
            minHeight: 0,
            maxWidth: 0,
            minWidth: 0
        }
    }

    $.uploader.Constructor = Uploader;


    /* UPLOADER NO CONFLICT
     * ================= */

    $.uploader.noConflict = function() {
        $.uploader = old;
        return this;
    }


    /* UPLOADER DATA-API
     * ============== */

    $(document).on('click.uploader.data-api', '[data-toggle="uploader"]', function(e) {
        var $this = $(this),
            $modal = $(".modal-uploader[data-role='uploader']"),
            option = $modal.data('uploader') ? 'toggle' : $modal.data('uploader'),
            limit = {
                mimeType: $this.attr('data-mimeType'),
                maxHeight: $this.attr('data-maxHeight'),
                maxWidth: $this.attr('data-maxWidth'),
                minHeight: $this.attr('data-minHeight'),
                minWidth: $this.attr('data-minWidth')
            },
            choose = $.extend($.uploader.defaults.choose, limit);

        e.preventDefault();
        $.uploader(option);
        $.uploader('switchMode', $this.attr('data-mode'));
        $.uploader('choose', this, choose);
    });

}(window, window.jQuery);
