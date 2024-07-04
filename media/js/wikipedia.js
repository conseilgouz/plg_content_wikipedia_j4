/**
 * Plugin Wikipedia : search wikipedia for selected text in an article
 *
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @copyright (c) 2024 ConseilGouz. All Rights Reserved.
 * @author ConseilGouz
 *
 * from https://awik.io/get-selected-text-and-cursor-position-javascript-to-show-popup-dialog
 */
document.addEventListener("DOMContentLoaded", function(){

body = document.querySelector('body');
var template = document.createElement("template");
var control = document.createElement("span");
control.id = "control";
template.appendChild(control);
body.appendChild(template);

document.querySelector('body').onpointerup = (event)=>{
    let selection = document.getSelection(), text = selection.toString();
    if (text == "") { // empty : return
        let control = document.querySelector('#control');
        if (control !== null) {
            alink =  control.querySelector('a');
            control.removeChild(alink);
            control.remove();
            document.getSelection().removeAllRanges();
        }
        return;
    }
    async function ask(text, event, control) {
        let userLang = navigator.language || navigator.userLanguage; 
        let lang = userLang.split('-') 
        let url = `https://api.wikimedia.org/core/v1/wikipedia/`+lang[0]+`/search/page?q=`+text+`&limit=1`;
        let response = await fetch( url);
        response.json()
            .then((data) => {
                alink =  control.querySelector('a');
                if (alink) control.removeChild(alink);

                resp = data.pages[0].description;
                // if (data.pages[1].description) resp+='\n'+data.pages[1].description;
                // Find out how much (if any) user has scrolled
                var scrollTop = (window.pageYOffset !== undefined) ? window.pageYOffset : (document.documentElement || document.body.parentNode || document.body).scrollTop;
                // Get cursor position
                const posX = event.clientX - 110;
                const posY = event.clientY + 20 + scrollTop;
                control.style.top = posY+'px';
                control.style.left = posX+'px';
                // control.innerText =  resp;
                alink = document.createElement("a");
                alink.setAttribute('href',"https://"+lang[0]+".wikipedia.org/wiki/"+data.pages[0].key);
                alink.setAttribute('target','_blank');
                alink.innerHTML = resp;
                control.appendChild(alink);
                document.body.appendChild(control);
                }
            )
            .catch(console.error);
        
    }
    ask(text, event, control);
}
})