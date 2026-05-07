/**
 * Emitter plugin
 */
import mitt from "mitt";

export default {
    install: (app, options) => {
        const emitter = mitt();

        app.provide('emitter', emitter);
    },
};
