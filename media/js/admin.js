/**
 * Plugin Wikipedia : search wikipedia for selected text in an article
 *
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @copyright (c) 2024 ConseilGouz. All Rights Reserved.
 * @author ConseilGouz
 */
document.addEventListener("DOMContentLoaded", function(){
    // check CG custom classes
    labels = document.querySelectorAll('#general .control-label');
    for(var i=0; i< labels.length; i++) {
        let label = labels[i];
        label.style.width = 'auto';
    }
    labels = document.querySelectorAll('#attrib-dictionary_set .control-label');
    for(var i=0; i< labels.length; i++) {
        let label = labels[i];
        label.style.width = 'auto';
    }
    fields = document.querySelectorAll('.view-plugin .hidefield');
    for(var i=0; i< fields.length; i++) {
        let field = fields[i];
        field.parentNode.parentNode.style.display = "none";
    }
    fields = document.querySelectorAll('.view-plugin .clear');
    for(var i=0; i< fields.length; i++) {
        let field = fields[i];
        field.parentNode.parentNode.style.clear = "both";
    }
    fields = document.querySelectorAll('.view-plugin .left');
    for(var i=0; i< fields.length; i++) {
        let field = fields[i];
        field.parentNode.parentNode.style.float = "left";
    }
    fields = document.querySelectorAll('.view-plugin .right');
    for(var i=0; i< fields.length; i++) {
        let field = fields[i];
        field.parentNode.parentNode.style.float = "right";
    }
    fields = document.querySelectorAll('.view-plugin .half');
    for(var i=0; i< fields.length; i++) {
        let field = fields[i];
        field.parentNode.parentNode.style.width = "50%";
    }
    fields = document.querySelectorAll('.view-plugin .thirty');
    for(var i=0; i< fields.length; i++) {
        let field = fields[i];
        field.parentNode.parentNode.style.width = "30%";
    }
    fields = document.querySelectorAll('.view-plugin .seventy');
    for(var i=0; i< fields.length; i++) {
        let field = fields[i];
        field.parentNode.parentNode.style.width = "70%";
    }
    fields = document.querySelectorAll('.view-plugin .gridauto');
    for(var i=0; i< fields.length; i++) {
        let field = fields[i];
        field.parentNode.parentNode.style.gridColumn = "auto";
    }
    reload = document.querySelector('#dict_reload');
    reload.addEventListener('click',function(e) {
        e.stopPropagation();
        e.preventDefault();
        reload.setAttribute('disabled', '');
        url = '?option=com_ajax&plugin=wikipedia&action=dictload&group=content&format=raw';
        Joomla.request({
            method   : 'POST',
            url   : url,
            onSuccess: function (data, xhr) {
                reload.removeAttribute('disabled'); 
                var parsed = JSON.parse(data);
                res = document.querySelector('#res');
                if (parsed.ret == 0) {
                    res.innerHTML = parsed.msg;
                } else {
                    res.innerHTML = parsed.msg;
                }
            }
        });
    })
})