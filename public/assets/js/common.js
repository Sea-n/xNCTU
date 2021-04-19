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
    const nav = document.getElementsByTagName("nav")[0];
    const body = document.getElementsByTagName("body")[0];
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
    const right = document.getElementById('nav-right');
    const navName = document.getElementById('nav-name');

    if (!navName)
        return;

    const prev = right.previousElementSibling;
    const rect = prev.getBoundingClientRect();

    let width = window.innerWidth - rect.right - 94;
    if (width < 0)
        width = 0;

    navName.style.maxWidth = width + 'px';
}

function updateTime() {
    document.querySelectorAll('time[data-ts]')
        .forEach((elem) => {
            const ts = parseInt(elem.dataset.ts);
            const time = timeFormat(ts);
            if (elem.innerText !== time)
                elem.innerText = time;
        });
}

function timeFormat(ts = 0) {
    if (ts < 1e10)
        ts *= 1000;

    if (ts === 0)
        ts = + new Date();

    const date = new Date(ts);
    const min = ('0' + date.getMinutes()).substr(-2);
    const hour = ('0' + date.getHours()).substr(-2);
    const day = ('0' + (date.getDate())).substr(-2);
    const mon = ('0' + (date.getMonth() + 1)).substr(-2);
    const year = date.getFullYear();

    const now = (new Date()).getTime();
    let dt = Math.floor(now / 1000) - Math.floor(ts / 1000);

    let time = `${hour}:${min}`;

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
    const paras = str.split('\n\n');
    for (let i=0; i<paras.length; i++) {
        const lines = paras[i].split('\n');
        for (let j=0; j<lines.length; j++) {
            const words = lines[j].split(' ');
            for (let k=0; k<words.length; k++) {
                let word = words[k];

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
                    const div = document.createElement('div');
                    div.appendChild(document.createTextNode(word));
                    word = div.innerHTML;
                }

                words[k] = word;
            }
            lines[j] = words.join(' ');
        }
        paras[i] = lines.join('<br>\n');
    }
    str = '<p>' + paras.join('</p>\n\n<p>') + '</p>';

    return str;
}

function showImg(e) {
    console.log(e);
    document.getElementById('img-container-inner').src = e.src || e.target.src;
    ts('#modal').modal('show');
}
