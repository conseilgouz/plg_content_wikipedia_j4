/**
 * Plugin Wikipedia : search wikipedia for selected text in an article
 *
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @copyright (c) 2024 ConseilGouz. All Rights Reserved.
 * @author ConseilGouz
 *
 * from https://awik.io/get-selected-text-and-cursor-position-javascript-to-show-popup-dialog
 */
var wikioptions;
document.addEventListener("DOMContentLoaded", function(){

wikioptions= Joomla.getOptions('plg_content_wikipedia');

let userLang = navigator.language || navigator.userLanguage; 
let lang = userLang.split('-') 

let save_text = "";

body = document.querySelector('body');
var template = document.createElement("template");
var control = document.createElement("span");
control.id = "control";
if (wikioptions.color)
    control.style.color = wikioptions.color;
if (wikioptions.bgcolor)
    control.style.backgroundColor = wikioptions.bgcolor;

template.appendChild(control);
body.appendChild(template);

document.querySelector('body').onpointerup = (event)=>{
    let selection = document.getSelection(), text = selection.toString();
    if (text == save_text) return;
    save_text = text;
    if (text == "") { // empty : return
        let control = document.querySelector('#control');
        if (control !== null) {
            alink =  control.querySelector('a');
            if (alink !== null) {
                control.removeChild(alink);
            }
            control.innerHTML = "";
            control.remove();
            document.getSelection().removeAllRanges();
        }
        return;
    }
    if (wikioptions.ajax == 'true') { // ajax mode
        ret = goAjax(event,text.trim().toLocaleLowerCase(),lang[0]);
    } else { // non ajax mode 
        textlang = (text.trim().toLocaleLowerCase())+'&'+lang[0];
        textall = (text.trim().toLocaleLowerCase())+'&*';
        if (typeof wikioptions.dictionary[textlang] != "undefined") { // text for browser language exists
            definition = wikioptions.dictionary[textlang].definition;
            url = wikioptions.dictionary[textlang].url;
            createControl(event,definition,url);
        } else if (typeof wikioptions.dictionary[textall] != "undefined") { // text for all languages
            definition = wikioptions.dictionary[textall].definition;
            url = wikioptions.dictionary[textall].url;
            createControl(event,definition,url)
        } else { // ask wikipedia
            ask(text, event, control);
        }
    }
}
async function ask(text, event, control) {
    let url = `https://api.wikimedia.org/core/v1/wikipedia/`+lang[0]+`/search/page?q=`+text+`&limit=1`;
    let response = await fetch( url);
    response.json()
        .then((data) => {
            alink =  control.querySelector('a');
            if (alink) control.removeChild(alink);
            if (!data.pages.length) return;
            resp = data.pages[0].description;
            wlink = "https://"+lang[0]+".wikipedia.org/wiki/"+data.pages[0].key;
            createControl(event,resp,wlink) 
            }
        )
        .catch(console.error);
}
function createControl(event,text,url) {
   // Find out how much (if any) user has scrolled
    var scrollTop = (window.pageYOffset !== undefined) ? window.pageYOffset : (document.documentElement || document.body.parentNode || document.body).scrollTop;
  // Get cursor position
    const posX = event.clientX - 20;
    const posY = event.clientY + 20 + scrollTop;
    control.style.top = posY+'px';
    control.style.left = posX+'px';
    if (!url) {
        control.innerHTML = text;
    } else {
        alink = document.createElement("a");
        alink.setAttribute('href',url);
        alink.setAttribute('target','_blank');
        alink.innerHTML = text;
        if (wikioptions.linkcolor)
            alink.style.color = wikioptions.linkcolor;
        control.appendChild(alink);
    }
    document.body.appendChild(control);
}
function goAjax(event,text,lang) {
    url = '?option=com_ajax&plugin=wikipedia&action=info&group=content&text='+ text+'&lang='+lang+'&format=raw';
    Joomla.request({
        method   : 'POST',
        url   : url,
        onSuccess: function (data, xhr) {
            var parsed = JSON.parse(data);
            if (parsed.ret == 0) {
               createControl(event,parsed.definition,parsed.url);
               return true;
            } else {
                return false;
            }
        }
    });
}
})