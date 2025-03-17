module.exports = {
	testEnvironment: 'jsdom',
	moduleFileExtensions: [ 'ts', 'tsx', 'js', 'json' ],
	moduleDirectories: [ 'node_modules' ],
	testMatch: [ "**/?(*.)+(spec|test).[tj]s?(x)" ], // Ensure your tests are recognized
	setupFiles: [
		"./jest.setup.js"
	],
	transform: {
		"^.+\\.(ts|tsx|js)$": "babel-jest" // Add .js extension to transform all JS files with babel-jest
	},
	transformIgnorePatterns: [
		"/node_modules/(?!diagram-js)/" // Add diagram-js to the list of modules to be transformed
	]
};
