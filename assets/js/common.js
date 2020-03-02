window.addEventListener("scroll", function () {
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
});


function init() {
	console.log("Hey there!");
	console.log("Source code: https://git.io/xNCTU");

	setInterval(() => {
		document.querySelectorAll('time[data-ts]')
		.forEach((elem) => {
			var ts = parseInt(elem.dataset.ts);
			var time = timeFormat(ts);
			elem.innerText = time;
		});
	}, 500);
}

window.addEventListener("load", init);

function timeFormat(ts = 0) {
	if (ts < 1e10)
		ts *= 1000;
	var date = new Date(ts);
	var min = ('0' + date.getMinutes()).substr(-2);
	var hour = ('0' + date.getHours()).substr(-2);
	var day = ('0' + (date.getDate())).substr(-2);
	var mon = ('0' + (date.getMonth()+1)).substr(-2);
	var year = date.getFullYear();

	var now = (new Date()).getTime();
	var dt = now - ts;

	if (dt < 0)
		return 'Error';

	var time = hour + ':' + min;

	dt = Math.floor(dt / 1000);
	if (dt < 120)
		return time + ' (' + dt + ' 秒前)';

	dt = Math.floor(dt / 60);
	if (dt < 90)
		return time + ' (' + dt + ' 分鐘前)';

	time = mon + ' 月 ' + day + ' 日 ' + time;

	dt = Math.floor(dt / 60);
	if (dt < 48)
		return time + ' (' + dt + ' 小時前)';

	dt = Math.floor(dt / 24);
	if (dt < 45)
		return time + ' (' + dt + ' 天前)';

	time = year + ' 年 ' + time;

	dt = Math.floor(dt / 30);
	return time + ' (' + dt + ' 個月前)';
}
