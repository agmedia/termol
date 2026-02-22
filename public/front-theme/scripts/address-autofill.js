(function () {
  'use strict';

  function normalize(value) {
    return String(value || '').trim().toLowerCase();
  }

  function hydrateScope(scope, places) {
    var cityInput = scope.querySelector('[data-address-city]');
    var postalInput = scope.querySelector('[data-address-postal]');
    var countySelect = scope.querySelector('[data-address-county]');
    var countrySelect = scope.querySelector('[data-address-country]');

    if (!cityInput || !postalInput) {
      return;
    }

    var lock = false;

    function applyPlace(place) {
      if (!place || lock) {
        return;
      }

      lock = true;

      if (cityInput) {
        cityInput.value = place.city || cityInput.value;
      }

      if (postalInput) {
        postalInput.value = place.postal_code || postalInput.value;
      }

      if (countySelect && place.county) {
        countySelect.value = place.county;
      }

      if (countrySelect && place.country_code) {
        countrySelect.value = place.country_code;
      }

      lock = false;
    }

    function findByCity() {
      var cityKey = normalize(cityInput.value);
      if (!cityKey) {
        return null;
      }

      return places.find(function (row) {
        return normalize(row.city) === cityKey;
      }) || null;
    }

    function findByPostal() {
      var postalKey = normalize(postalInput.value);
      if (!postalKey) {
        return null;
      }

      return places.find(function (row) {
        return normalize(row.postal_code) === postalKey;
      }) || null;
    }

    cityInput.addEventListener('change', function () {
      applyPlace(findByCity());
    });

    cityInput.addEventListener('blur', function () {
      applyPlace(findByCity());
    });

    postalInput.addEventListener('change', function () {
      applyPlace(findByPostal());
    });

    postalInput.addEventListener('blur', function () {
      applyPlace(findByPostal());
    });
  }

  function init() {
    var roots = document.querySelectorAll('[data-address-autofill]');
    if (!roots.length) {
      return;
    }

    roots.forEach(function (root) {
      var sourceUrl = root.getAttribute('data-address-source');
      if (!sourceUrl) {
        return;
      }

      fetch(sourceUrl, { credentials: 'same-origin' })
        .then(function (response) { return response.ok ? response.json() : null; })
        .then(function (payload) {
          if (!payload || !Array.isArray(payload.places)) {
            return;
          }

          var scopes = root.querySelectorAll('[data-address-scope]');
          scopes.forEach(function (scope) {
            hydrateScope(scope, payload.places);
          });
        })
        .catch(function () {
          // Fail silently: form stays fully manual.
        });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
