var submitted = false;
var img_data = false;

function init() {
	if (document.getElementById('submit-post')) {
		var img = document.getElementById('img');
		document.getElementById('submit-post').onsubmit = submitForm;
		document.getElementById('body-area').oninput = formUpdate;
		img.oninput = formUpdate;
		img.onchange = updateImg;
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


		window.addEventListener('paste', (e) => {
			var items = e.clipboardData.items;
			if(items == undefined)
				return;

			for (var i=0; i<items.length; i++) {
				if (items[i].type.indexOf('image') == -1)
					continue;

				var blob = items[i].getAsFile();
				img_data = blob;

				updateImgPreview();
			}
		});


		restoreForm();
	}
}

function updateImg() {
	var img = document.getElementById('img');
	var files = img.files;

	if (!files || !files[0])
		return;

	img_data = files[0];
	updateImgPreview();
}

function updateImgPreview() {
	var preview = document.getElementById('img-preview');

	if (!img_data) {
		preview.src = '';
		preview.parentElement.setAttribute('style', 'display: none !important;');
		return;
	}

	if (img_data.size > 50*1000*1000) {
		alert('圖片檔案過大！請壓縮至 50 MB 以下');
		img_data = false;
		updateImgPreview();
		return;
	}

	var reader = new FileReader();
	reader.onload = (e) => {
		preview.src = reader.result;
		preview.parentElement.style.display = '';
	};
	reader.readAsDataURL(img_data);
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
	var captchaInput = document.getElementById('captcha-input');
	var captchaField = document.getElementById('captcha-field');
	var submit = document.getElementById('submit');
	var preview = document.getElementById('warning-preview');

	bodyField.classList.remove('error', 'warning');
	captchaField.classList.remove('error');
	submit.classList.remove('disabled', 'negative', 'basic');

	var body = bodyArea.value;
	var len = body.length;
	bodyWc.innerText = len;

	if (img_data) {
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

	preview.style.display = 'none';
	if (body.includes('http')) {
		var lines = body.split('\n');

		var last = lines[lines.length - 1];
		if (!last.match(/^https?:\/\/[^\s]*$/)) {
			preview.style.display = '';
			submit.classList.add('negative');
		}

		/* Even if last line is URL, first line cannot be URL. */
		var first = lines[0];
		if (first.match(/https?:\/\//)) {
			preview.style.display = '';
			submit.classList.add('negative');
			submit.classList.add('disabled');
		}
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
	var captcha = document.getElementById('captcha-input');
	var anon = document.getElementById('anon');
	var csrf = document.getElementById('csrf_token');
	var submit = document.getElementById('submit');

	if (!checkForm()) {
		return;
	}

	submit.classList.add('disabled');

	const formData  = new FormData();
	formData.append('body', body.value);
	if (img_data)
		formData.append('img', img_data);
	formData.append('captcha', captcha.value);
	formData.append('csrf_token', csrf.value);
	if (anon.checked)
		formData.append('anon', 1);

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

		if (img_data && !resp.has_img) {
			alert('圖片上傳失敗！');
			return;
		}

		localStorage.setItem('draft', '');
		submitted = true;
		location.href = '/review/' + resp.uid;
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
	if (len == 0) {
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

function changeAnon() {
	var anon = document.getElementById('anon');
	var wName = document.getElementById('warning-name');
	var wIp = document.getElementById('warning-ip');

	wName.style.display = 'none';
	wIp.style.display = 'none';
	if (anon.checked)
		wIp.style.display = '';
	else
		wName.style.display = '';
}

window.addEventListener("load", init);
