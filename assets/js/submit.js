var stopCountdown = 0;
var submitted = false;

function init() {
	if (document.getElementById('submit-post')) {
		document.getElementById('submit-post').onsubmit = checkForm;
		document.getElementById('body-area').oninput = formUpdate;
		document.getElementById('img').oninput = formUpdate;
		document.getElementById('captcha-input').oninput = formUpdate;

		window.addEventListener("beforeunload", function (e) {
			var bodyArea = document.getElementById('body-area');
			var len = bodyArea.value.length;
			if (len == 0 || submitted)
				return undefined;

			var confirmationMessage = '您確定要離開嗎？';
			(e || window.event).returnValue = confirmationMessage; //Gecko + IE
			return confirmationMessage; //Gecko + Webkit, Safari, Chrome etc.
		});

		restoreForm();
	}

	if (document.getElementById('post-preview')) {
		var deadline = (new Date()).getTime() + 3*60*1000;
		stopCountdown = setInterval(() => {
			var dt = Math.floor((deadline - (new Date()).getTime()) / 1000);
			if (dt == 0) {
				document.getElementById('delete-button').classList.add('disabled');
				clearInterval(stopCountdown);
			}

			var min = Math.floor(dt/60);
			var sec = dt % 60;
			if (sec < 10)
				sec = '0' + sec;
			document.getElementById('countdown').innerText = min + ':' + sec;
		}, 100);
	}
}

function restoreForm() {
	var bodyArea = document.getElementById('body-area');
	var captchaInput = document.getElementById('captcha-input');

	if (localStorage.getItem('draft'))
		bodyArea.value = localStorage.getItem('draft');

	if (localStorage.getItem('captcha'))
		captchaInput.value = localStorage.getItem('captcha');

	formUpdate();
}

function formUpdate() {
	var bodyArea = document.getElementById('body-area');
	var bodyField = document.getElementById('body-field');
	var bodyWc = document.getElementById('body-wc');
	var img = document.getElementById('img');
	var captchaInput = document.getElementById('captcha-input');
	var captchaField = document.getElementById('captcha-field');
	var submit = document.getElementById('submit');

	bodyField.classList.remove('error', 'warning');
	captchaField.classList.remove('error');
	submit.classList.remove('disabled');

	var body = bodyArea.value;
	var len = body.length;
	bodyWc.innerText = len;

	if (img.files.length) {
		if (len > 870) {
			bodyField.classList.add('error');
		} else if (len > 690)
			bodyField.classList.add('warning');
	} else {
		if (len > 3600) {
			bodyField.classList.add('error');
		} else if (len > 3200)
			bodyField.classList.add('warning');
	}

	var captcha = captchaInput.value;
	if (captcha.length > 0 &&
		captcha.length != captchaInput.dataset.len)
		captchaField.classList.add('error');

	localStorage.setItem('draft', body);
	localStorage.setItem('captcha', captcha);
}

function checkForm(e) {
	var bodyArea = document.getElementById('body-area');
	var bodyField = document.getElementById('body-field');
	var img = document.getElementById('img');
	var captchaInput = document.getElementById('captcha-input');
	var captchaField = document.getElementById('captcha-field');
	submitted = true;

	formUpdate();  // For clean & update warnings

	var len = bodyArea.value.length;
	if (len < 10) {
		bodyField.classList.add('error');
		e.preventDefault();
		submitted = false;
	}

	if ((img.files.length && len > 870) || len > 3600) {
		e.preventDefault();
		submitted = false;
	}

	if (captchaInput.value.length != captchaInput.dataset.len) {
		captchaField.classList.add('error');
		e.preventDefault();
		submitted = false;
	}

	if (submitted) {
		localStorage.setItem('draft', '');
	}
}

function deleteSubmission(uid) {
	if (!confirm('您確定要刪除此投稿嗎？'))
		return;

	var reason = prompt('請輸入刪除附註');
	if (reason.length < 5) {
		alert('請輸入 5 個字以上');
		return;
	}

	data = {
		action: 'delete',
		uid: uid,
		reason: reason
	};

	fetch('/api', {
		method: 'POST',
		body: JSON.stringify(data),
		headers: {'content-type': 'application/json'}
	}).then(resp => resp.json())
	.then((resp) => {
		console.log(resp);
		if (resp.ok) {
			document.getElementById('delete-button').classList.add('disabled');
			clearInterval(stopCountdown);
		}
		alert(resp.msg);
	});
}

window.addEventListener("load", init);
