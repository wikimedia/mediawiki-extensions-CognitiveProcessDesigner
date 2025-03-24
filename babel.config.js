module.exports = {
	presets: [
		[
			"@babel/preset-env",
			{
				targets: "last 3 years" // really old ie 6-8
			}
		],
		"@babel/preset-typescript"
	],
	plugins: [
		"@babel/plugin-transform-modules-commonjs",
		"@babel/plugin-proposal-class-properties",
		"@babel/plugin-transform-private-methods"
	]
};
