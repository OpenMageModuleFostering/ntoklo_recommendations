/**
* nToklo
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
*
* @category   Ntoklo
* @package    Ntoklo_Recommendations
* @copyright  Copyright (c) 2013 nToklo (http://ntoklo.com)
* @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
* @author     nToklo
*/

// Fix prototype 1.6 conversion of JSON Array to Strings
if(window.Prototype) {
    delete Array.prototype.toJSON;
    //delete Object.prototype.toJSON;
    //delete Hash.prototype.toJSON;
    //delete String.prototype.toJSON;
}

// Ntoklo submit UV implementation
var Ntoklo = Class.create({

    initialize: function(ntoklo_js_url, universal_variable) {
        // initialize the UV
        this.uv = universal_variable;
        // initialize ntoklo.js
        this.nt = {'src' : ntoklo_js_url,
                    'type': 'text/javascript',
                    'async': true,
                    'id': 'ntoklo_js'};

        // register recommended clickEvent
        var ntoklo = this;
        $$('.ntoklo_conversion').each(function(elem){
            $(elem).observe('click', function(){
                ntoklo.clickEvent('conversion');
            });
        });

//        //register rate clickEvent
//        if ($('review-form')) {
//            $('review-form').observe('submit', function(event){
//                if (!event.stopped) {
//                    event.preventDefault();
//                    ntoklo.clickEvent('review');
//                }
//            });
//        }
    },

    typeOf: function(value) {
        var s = typeof value;
        if (s === 'object') {
            if (value) {
                if (Object.prototype.toString.call(value) == '[object Array]') {
                    s = 'array';
                }
            } else {
                s = 'null';
            }
        }
        return s;
    },

    mergeUv: function(destObj, srcObj) {
        if (this.typeOf(srcObj) !== 'object' || this.typeOf(srcObj) === 'undefined') {
            srcObj = this.uv;
        }
        if (this.typeOf(destObj) !== 'object' && typeof window.universal_variable === 'object') {
            destObj = window.universal_variable;
        }
        for( var p in srcObj ) {
            if( this.typeOf(destObj[p]) === 'undefined' ) {
                destObj[p] = ( this.typeOf(srcObj[p]) === 'array' ) ? [] : {};
                destObj[p] = ( this.typeOf(srcObj[p]) === 'object' ) ? this.mergeUv(destObj[p], srcObj[p]) : srcObj[p];
            } else {
                if ( this.typeOf(srcObj[p]) === 'object' ) {
                    this.mergeUv(destObj[p], srcObj[p]);
                }
            }
        }
        return destObj;
    },

    submitUv: function (callback) {
        // Debug
        console.log(window.universal_variable);

        if ($(this.nt.id)) {
            this.nt.id += '_n';
        }
        var nt = document.createElement('script');
        nt.type = this.nt.type; nt.async = this.nt.async; nt.src = this.nt.src; nt.id = this.nt.id;
        var head = document.getElementsByTagName('head')[0]; head.insertBefore(nt, head.firstChild);
        nt.onload = nt.onreadystatechange = function() {
            if (this.readyState == "loaded" || this.readyState == "complete") {
                if (callback) {
                    callback();
                }
            }
        }
    },

    setConversion: function() {
        var pagesource = window.universal_variable.page;
        document.cookie = "ntoklo_conversion=" + JSON.stringify(pagesource)+ "; path="+ Mage.Cookies.path+ "; domain = "+ Mage.Cookies.domain+ "";
    },

    loadEvent: function() {
        window.universal_variable = this.mergeUv();
        this.submitUv();
    },

    clickEvent: function(event_type) {
        if (!event_type) {
            return false;
        }
        window.universal_variable = this.mergeUv();
        switch (event_type) {
            case 'review':
                window.universal_variable.events.items = new Array({'type' : _ntoklo_event_rate}, {'type' : _ntoklo_event_review});
                this.submitUv(this.reviewCallback);
                break;
            case 'conversion':
                this.setConversion();
                break;
        }
        return true;
    },

    reviewCallback: function() {
        $('review-form').submit();
    }
});