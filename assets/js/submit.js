var stopCountdown = 0;
var submitted = false;

function init() {
	if (document.getElementById('submit-post')) {
		document.getElementById('submit-post').onsubmit = checkFormSubmit;
		document.getElementById('body-area').oninput = checkForm;
		document.getElementById('img').oninput = checkForm;
		document.getElementById('captcha-input').oninput = checkForm;
		checkForm();

		window.addEventListener("beforeunload", function (e) {
			var bodyArea = document.getElementById('body-area');
			var len = bodyArea.value.length;
			if (len == 0 || submitted)
				return undefined;

			var confirmationMessage = '您確定要離開嗎？';
			(e || window.event).returnValue = confirmationMessage; //Gecko + IE
			return confirmationMessage; //Gecko + Webkit, Safari, Chrome etc.
		});
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

function checkForm() {
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

	var len = bodyArea.value.length;
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

	if (captchaInput.value.length > 0 &&
		captchaInput.value.length != captchaInput.dataset.len)
		captchaField.classList.add('error');
}

function checkFormSubmit(e) {
	e.preventDefault();
	var bodyArea = document.getElementById('body-area');
	var bodyField = document.getElementById('body-field');
	var img = document.getElementById('img');
	var captchaInput = document.getElementById('captcha-input');
	var captchaField = document.getElementById('captcha-field');
	var result = true;

	checkForm();  // For clean & update warnings

	var len = bodyArea.value.length;
	if (len < 10) {
		result = false;
		bodyField.classList.add('error');
	}

	if (img.files.length) {
		if (len > 870)
			result = false;
	} else {
		if (len > 3600)
			result = false;
	}

	if (captchaInput.value.length != captchaInput.dataset.len) {
		result = false;
		bodyField.classList.add('error');
		captchaField.classList.add('error');
	}

	if (result)
		submitted = true;

	return result;
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
