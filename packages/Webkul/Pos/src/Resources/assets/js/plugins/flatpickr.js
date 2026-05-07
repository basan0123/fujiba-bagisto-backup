import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.css";
import { Spanish } from "flatpickr/dist/l10n/es.js";
import { Catalan } from "flatpickr/dist/l10n/cat.js";
import { Arabic } from "flatpickr/dist/l10n/ar.js";
import { Persian } from "flatpickr/dist/l10n/fa.js";
import { Turkish } from "flatpickr/dist/l10n/tr.js";
import { Bengali } from "flatpickr/dist/l10n/bn.js";
import { German } from "flatpickr/dist/l10n/de.js";
import { English } from "flatpickr/dist/l10n/default.js";
import { French } from "flatpickr/dist/l10n/fr.js";
import { Hebrew } from "flatpickr/dist/l10n/he.js";
import { Hindi } from "flatpickr/dist/l10n/hi.js";
import { Italian } from "flatpickr/dist/l10n/it.js";
import { Japanese } from "flatpickr/dist/l10n/ja.js";
import { Dutch } from "flatpickr/dist/l10n/nl.js";
import { Polish } from "flatpickr/dist/l10n/pl.js";
import { Portuguese } from "flatpickr/dist/l10n/pt.js";
import { Russian } from "flatpickr/dist/l10n/ru.js";
import { Sinhala } from "flatpickr/dist/l10n/si.js";
import { Ukrainian } from "flatpickr/dist/l10n/uk.js";
import { Chinese } from "flatpickr/dist/l10n/zh.js";

// Manual Myanmar Translation
const Myanmar = {
    weekdays: {
        shorthand: ["တနင်္ဂနွေ", "တနင်္လာ", "အင်္ဂါ", "ဗုဒ္ဓဟူး", "ကြာသပတေး", "သောကြာ", "စနေ"],
        longhand: ["တနင်္ဂနွေ", "တနင်္လာ", "အင်္ဂါ", "ဗုဒ္ဓဟူး", "ကြာသပတေး", "သောကြာ", "စနေ"],
    },
    months: {
        shorthand: ["ဇန်", "ဖေ", "မတ်", "ဧ", "မေ", "ဇွန်", "ဇူ", "သြ", "စက်", "အောက်", "နို", "ဒီ"],
        longhand: ["ဇန်နဝါရီ", "ဖေဖော်ဝါရီ", "မတ်", "ဧပြီ", "မေ", "ဇွန်", "ဇူလိုင်", "သြဂုတ်", "စက်တင်ဘာ", "အောက်တိုဘာ", "နိုဝင်ဘာ", "ဒီဇင်ဘာ"],
    },
    firstDayOfWeek: 1,
};

const localeMap = {
    es: Spanish,
    ca: Catalan,
    ar: Arabic,
    fa: Persian,
    tr: Turkish,
    bn: Bengali,
    de: German,
    en: English,
    fr: French,
    he: Hebrew,
    hi: Hindi,
    it: Italian,
    ja: Japanese,
    nl: Dutch,
    pl: Polish,
    pt: Portuguese,
    ru: Russian,
    si: Sinhala,
    uk: Ukrainian,
    zh: Chinese,
    my: Myanmar, // canonical Fujiba Burmese locale code
};

export default {
    install: (app) => {
        const lang = document.documentElement.lang || "en";

        const locale = localeMap[lang];

        if (locale) {
            flatpickr.localize(locale);
        }

        app.provide("flatpickr", flatpickr);

        const changeTheme = (theme) => {
            const existingTheme = document.getElementById("flatpickr-theme");

            if (existingTheme) {
                existingTheme.remove();
            }

            if (theme === "light") {
                return;
            }

            const link = document.createElement("link");

            const baseUrl = document.querySelector('meta[name="base-url"]').getAttribute('content');

            link.id = "flatpickr-theme";
            link.rel = "stylesheet";
            link.type = "text/css";
            link.href = `${baseUrl}/flatpickr/themes/${theme}.css`;

            document.head.appendChild(link);
        };

        const isDark = document.documentElement.classList.contains("dark");

        changeTheme(isDark ? "dark" : "airbnb");

        const observer = new MutationObserver(() => {
            const dark = document.documentElement.classList.contains("dark");

            changeTheme(dark ? "dark" : "airbnb");
        });

        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ["class"],
        });
    },
};