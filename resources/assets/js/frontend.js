(function () {
  function attach(form) {
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      var status = form.querySelector('.ddys-laravel-status');
      if (status) {
        status.textContent = 'Submitting...';
      }

      fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        credentials: 'same-origin',
        headers: {
          Accept: 'application/json'
        }
      }).then(function (response) {
        return response.json().then(function (payload) {
          return { ok: response.ok, payload: payload };
        });
      }).then(function (result) {
        if (!result.ok || result.payload.success === false) {
          if (status) {
            status.textContent = result.payload.message || 'Submission failed.';
          }
          return;
        }

        if (status) {
          status.textContent = 'Submitted.';
        }
        if (form.matches('.ddys-laravel-request-form')) {
          form.reset();
        }
      }).catch(function () {
        if (status) {
          status.textContent = 'Network error. Please try again later.';
        }
      });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-ddys-laravel-request-form]').forEach(function (form) {
      if (!form.dataset.ddysLaravelBound) {
        form.dataset.ddysLaravelBound = '1';
        attach(form);
      }
    });
  });
}());

