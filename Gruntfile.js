const fs = require('fs');
const path = require('path');
const { rollup } = require('rollup');
const { nodeResolve } = require('@rollup/plugin-node-resolve');
const commonjs = require('@rollup/plugin-commonjs');
const { terser } = require('rollup-plugin-terser');

module.exports = function (grunt) {
    grunt.registerTask('amd', 'Build AMD modules', async function () {
        const done = this.async();
        const srcDir = 'amd/src';
        const destDir = 'amd/build';

        if (!fs.existsSync(destDir)) {
            fs.mkdirSync(destDir, { recursive: true });
        }

        const files = fs.readdirSync(srcDir).filter(file => file.endsWith('.js'));

        try {
            for (const file of files) {
                const inputPath = path.join(srcDir, file);
                const outputPath = path.join(destDir, file.replace('.js', '.min.js'));

                const bundle = await rollup({
                    input: inputPath,
                    plugins: [
                        nodeResolve(),
                        commonjs(),
                        terser()
                    ]
                });

                await bundle.write({
                    file: outputPath,
                    format: 'amd'
                });

                grunt.log.writeln(`✔ Built ${file} → ${outputPath}`);
            }
            done();
        } catch (err) {
            grunt.log.error(err);
            done(false);
        }
    });

    // No-op: this plugin ships no CSS/SCSS. moodle-plugin-ci's "grunt" command
    // schedules a "stylelint" task by default alongside "amd", and fails outright
    // with "Task not found" if the Gruntfile doesn't define one - registering it
    // here (even as a no-op) keeps that check honest and passing.
    grunt.registerTask('stylelint', 'No stylesheets in this plugin.', function() {
        grunt.log.writeln('No CSS/SCSS files to lint.');
    });
};
