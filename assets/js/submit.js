var stopCountdown = 0;
var submitted = false;

function init() {
	if (document.getElementById('submit-post')) {
		document.getElementById('submit-post').onsubmit = submitForm;
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

function submitForm(e) {
	e.preventDefault();
	var body = document.getElementById('body-area');
	var img = document.getElementById('img');
	var captcha = document.getElementById('captcha-input');
	var csrf = document.getElementById('csrf_token');
	submitted = true;

	if (!checkForm()) {
		e.preventDefault();
		submitted = false;

		return;
	}

	const formData  = new FormData();
	formData.append('body', body.value);
	if (img.files.length)
		formData.append('img', img.files[0]);
	formData.append('captcha', captcha.value);
	formData.append('csrf_token', csrf.value);

	fetch('/api/submission', {
		method: 'POST',
		body: formData,
	}).then(resp => resp.json())
	.then((resp) => {
		console.log(resp);
		if (!resp.ok) {
			alert(resp.msg);
			return;
		}

		showPreview(resp);

		var deadline = (new Date()).getTime() + 10*1000;
		stopCountdown = setInterval(() => {
			var dt = Math.floor((deadline - (new Date()).getTime()) / 1000);
			if (dt <= 0) {
				document.getElementById('confirm-button').classList.remove('disabled');
				clearInterval(stopCountdown);
			}

			var min = Math.floor(dt/60);
			var sec = dt % 60;
			if (sec < 10)
				sec = '0' + sec;
			document.getElementById('countdown').innerText = min + ':' + sec;
		}, 100);
	});
}

function checkForm() {
	var bodyArea = document.getElementById('body-area');
	var bodyField = document.getElementById('body-field');
	var img = document.getElementById('img');
	var captchaInput = document.getElementById('captcha-input');
	var captchaField = document.getElementById('captcha-field');
	var isValid = true;

	formUpdate();  // For clean & update warnings

	var len = bodyArea.value.length;
	if (len < 10) {
		bodyField.classList.add('error');
		isValid = false;
	}

	if ((img.files.length && len > 870) || len > 3600) {
		isValid = false;
	}

	if (captchaInput.value.length != captchaInput.dataset.len) {
		captchaField.classList.add('error');
		isValid = false;
	}

	return isValid;
}

function showPreview(data) {
	document.body.dataset.uid = data.uid;
	document.getElementById('preview-body').innerText = data.body;

	var img = document.getElementById('preview-img');
	if (data.has_img)
		img.src = '/img/' + data.uid + '.jpg';
	else
		img.src = '';

	document.getElementById('author-photo').src = data.author_photo;
	document.getElementById('author-name').innerText = data.author_name;
	document.getElementById('author-ip').innerText = data.ip_masked;

	document.getElementById('preview-section').style.display = '';
	document.getElementById('submit-section').style.display = 'none';
}

function confirmSubmission() {
	if (!confirm('確定要送出此投稿嗎？\n\n這是你反悔的最後機會'))
		return;

	document.getElementById('confirm-button').classList.add('disabled');
	document.getElementById('delete-button').classList.add('disabled');

	var uid = document.body.dataset.uid;

	data = {
		uid: uid,
		status: 'confirmed',
	};

	fetch('/api/submission', {
		method: 'PATCH',
		body: JSON.stringify(data),
		headers: {'content-type': 'application/json'}
	}).then(resp => resp.json())
	.then((resp) => {
		console.log(resp);
		if (!resp.ok) {
			alert(resp.msg);
			return;
		}
		localStorage.setItem('draft', '');
		location.href = '/review?uid=' + uid;
	});
}

function deleteSubmission() {
	if (!confirm('您確定要刪除此投稿嗎？'))
		return;

	var reason = prompt('請輸入刪除附註');
	if (reason.length < 5) {
		alert('請輸入 5 個字以上');
		return;
	}

	document.getElementById('confirm-button').classList.add('disabled');
	document.getElementById('delete-button').classList.add('disabled');

	clearInterval(stopCountdown);

	data = {
		uid: document.body.dataset.uid,
		reason: reason
	};

	fetch('/api/submission', {
		method: 'DELETE',
		body: JSON.stringify(data),
		headers: {'content-type': 'application/json'}
	}).then(resp => resp.json())
	.then((resp) => {
		console.log(resp);
		alert(resp.msg);
	});
}

window.addEventListener("load", init);
