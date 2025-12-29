const App = {
    CSRFToken: null,
    meta: null,
    CHECKMARK: '<span style="color:#00C000;">&#10004;</span>',
    XMARK: '<span style="color:#C00000;font-weight:bold;font-size:16px;">&times;</span>',
    debug: true,

    init: function () {
        const self = this;
        let metaContent = document.querySelector('meta[name="csrf-token"]');
        this.CSRFToken = (metaContent != null ? metaContent.getAttribute('content') : null);
        metaContent = document.querySelector('meta[name="app-meta"]');
        this.meta = (metaContent != null ? metaContent.getAttribute('content') : null);
    },

    submitForm: function (form, route) {
        if (typeof form === 'undefined') {
            return;
        }

        if (typeof route !== 'undefined') {
            const input = document.createElement('INPUT');
            input.setAttribute('type', 'hidden');
            input.setAttribute('name', 'r');
            input.value = route;
            form.appendChild(input);
        }

        if (typeof form.token === 'undefined') {
            const input = document.createElement('INPUT');
            input.setAttribute('type', 'hidden');
            input.setAttribute('name', 'token');
            form.appendChild(input);
        }

        if (typeof form.binfo === 'undefined') {
            const input = document.createElement('INPUT');
            input.setAttribute('type', 'hidden');
            input.setAttribute('name', 'binfo');
            form.appendChild(input);
        }

        if (typeof form.action === 'undefined' || form.action === '') {
            form.action = 'index.php';
        }

        form.token.value = this.CSRFToken;
        form.binfo.value = `{"browserWidth":${window.innerWidth}}`;
        form.submit();
    },

    /**
     * Submits form via Ajax (fetch) as POST. Returns data.
     * 
     * @param string method 
     * @param {*} form|FormData
     * @param string route
     * @param int timeout
     * @returns string|json
     */
    submitAjax: async function (method, form, route, timeout) {
        if (typeof form === 'undefined') {
            return;
        }

        let formData = (form instanceof FormData ? form : new FormData(form));

        if (formData.get('token') == null) {
            formData.append('token', this.CSRFToken);
        }

        if (typeof timeout === 'undefined') {
            timeout = 20000;
        }

        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), timeout);

        const opts = {
            method: method,
            signal: controller.signal,
        }

        let url = route;
        method = method.toUpperCase();

        if (method == 'GET') {
            url = route+'?'+new URLSearchParams(formData).toString();
        }
        else if (method == 'POST') {
            opts['body'] = formData;
        }
        else if (method == 'DELETE') {
            // Convert to query string
            opts['body'] = new URLSearchParams(formData).toString();
        }

        // console.log('url', url);
        // console.log('opts', opts);

        try {
            const resp = await fetch(url, opts);

            if (!resp.ok) {
                throw new Error(`${resp.status} ${resp.statusText}`)
            }

            let data = await resp.text();

            if (data.startsWith('{') && data.trim().endsWith('}')) {
                data = JSON.parse(data);
            }

            return data;
        }
        catch (error) {
            console.error(error)
        }
        finally {
            clearTimeout(timeoutId);
        }
    },

    DOMCreateOption: function (value, text) {
        const opt = document.createElement('OPTION');
        opt.value = value;
        opt.appendChild(document.createTextNode(text));
        return opt;
    },

    log: function (...args) {
        if (this.debug) {
            if (args.length == 1) {
                console.log(args[0]);
                return;
            }
            console.log(args[0], args[1]);
        }
    }
}

App.init();
