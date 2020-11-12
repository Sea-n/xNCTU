window.addEventListener("load", init);
window.addEventListener("scroll", adjustNav);
window.addEventListener("resize", resizeNav);


function init() {
    console.log("Hey there!");
    console.log("Source code: https://git.io/xNCTU");

    resizeNav();
    adjustNav();
    updateTime();

    setInterval(updateTime, 200);
}

function adjustNav() {
    var nav = document.getElementsByTagName("nav")[0];
    var body = document.getElementsByTagName("body")[0];
    if (window.scrollY > 200) {
        nav.classList.add("fixed");
        body.style.top = "40px";
    }
    if (window.scrollY < 20) {
        nav.classList.remove("fixed");
        body.style.top = "0px";
    }
}

function resizeNav() {
    var right = document.getElementById('nav-right');
    var navName = document.getElementById('nav-name');

    if (!navName)
        return;

    var prev = right.previousElementSibling;
    var rect = prev.getBoundingClientRect();

    var width = window.innerWidth - rect.right - 94;
    if (width < 0)
        width = 0;

    navName.style.maxWidth = width + 'px';
}

function updateTime() {
    document.querySelectorAll('time[data-ts]')
        .forEach((elem) => {
            var ts = parseInt(elem.dataset.ts);
            var time = timeFormat(ts);
            if (elem.innerText !== time)
                elem.innerText = time;
        });
}

function timeFormat(ts = 0) {
    if (ts < 1e10)
        ts *= 1000;

    if (ts === 0)
        ts = + new Date();

    var date = new Date(ts);
    var min = ('0' + date.getMinutes()).substr(-2);
    var hour = ('0' + date.getHours()).substr(-2);
    var day = ('0' + (date.getDate())).substr(-2);
    var mon = ('0' + (date.getMonth()+1)).substr(-2);
    var year = date.getFullYear();

    var now = (new Date()).getTime();
    var dt = Math.floor(now / 1000) - Math.floor(ts / 1000);

    var time = `${hour}:${min}`;

    if (dt < 0)
        return `${time} (${-dt} 秒後)`;

    if (dt < 120)
        return `${time} (${dt} 秒前)`;

    dt = Math.floor(now / 1000 / 60) - Math.floor(ts / 1000 / 60);
    if (dt < 90)
        return `${time} (${dt} 分鐘前)`;

    time = `${mon} 月 ${day} 日 ${time}`;

    dt = Math.floor(dt / 60);
    if (dt < 48)
        return `${time} (${dt} 小時前)`;

    dt = Math.floor(dt / 24);
    if (dt < 45)
        return `${time} (${dt} 天前)`;

    time = `${year} 年 ${time}`;

    dt = Math.floor(dt / 30);
    return `${time} (${dt} 個月前)`;
}

function toHTML(str) {
    var paras = str.split('\n\n');
    for (var i=0; i<paras.length; i++) {
        var lines = paras[i].split('\n');
        for (var j=0; j<lines.length; j++) {
            var words = lines[j].split(' ');
            for (var k=0; k<words.length; k++) {
                var word = words[k];

                const a = document.createElement('a');
                if (/^https?:\/\/.+\..+/.test(word)) {
                    const linkText = document.createTextNode(word);
                    a.appendChild(linkText);
                    a.href = word;
                    a.target = '_blank';
                    word = a.outerHTML;
                } else if (/^#靠交\d+$/.test(word)) {
                    a.appendChild(document.createTextNode(word));
                    a.href = 'https://x.nctu.app/post/' + word.substr(3);
                    a.target = '_blank';
                    word = a.outerHTML;
                } else if (/^#靠清\d+$/.test(word)) {
                    a.appendChild(document.createTextNode(word));
                    a.href = 'https://x.nthu.io/post/' + word.substr(3);
                    a.target = '_blank';
                    word = a.outerHTML;
                } else if (/^#告白交大\d+$/.test(word)) {
                    a.appendChild(document.createTextNode(word));
                    a.href = 'https://crush.nctu.app/post/' + word.substr(5);
                    a.target = '_blank';
                    word = a.outerHTML;
                } else if (/^#投稿\w+$/.test(word)) {
                    a.appendChild(document.createTextNode(word));
                    a.href = '/review/' + word.substr(3);
                    a.target = '_blank';
                    word = a.outerHTML;
                } else if (/^#[^\s]+$/.test(word)) {
                    a.appendChild(document.createTextNode(word));
                    a.href = 'javascript:;';
                    word = a.outerHTML;
                } else {
                    let div = document.createElement('div');
                    div.appendChild(document.createTextNode(word));
                    word = div.innerHTML;
                }

                words[k] = word;
            }
            lines[j] = words.join('&nbsp;');
        }
        paras[i] = lines.join('<br>\n');
    }
    str = '<p>' + paras.join('</p>\n\n<p>') + '</p>';

    return str;
}

function showImg(e) {
    console.log(e);
    var src = e.src || e.target.src;
    document.getElementById('img-container-inner').src = src;
    ts('#modal').modal('show');
}
