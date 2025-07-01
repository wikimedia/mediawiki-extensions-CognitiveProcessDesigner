const path = require( 'path' );

module.exports = {
	mode: 'production',
	entry: {
		viewer: './resources/js/cpd/CpdViewer.ts',
		modeler: './resources/js/cpd/CpdModeler.ts',
		bpmnDiffer: './resources/js/cpd/CpdBpmnDiffer.ts'
	},
	output: {
		path: path.resolve( __dirname, 'resources/js/dist' ),
		filename: 'cpd.[name].bundle.js',
		libraryTarget: 'commonjs2'
	},
	resolve: {
		extensions: [ '.ts', '.js', '.json' ]
	},
	module: {
		rules: [ {
			test: /\.bpmn$/,
			type: 'asset/source'
		}, {
			test: /\.ts?$/,
			exclude: /node_modules/,
			loader: 'ts-loader'
		} ]
	},
	watchOptions: {
		// for some systems, watching many files can result in a lot of CPU or memory usage
		// https://webpack.js.org/configuration/watch/#watchoptionsignored
		// don't use this pattern, if you have a monorepo with linked packages
		ignored: /node_modules/
	}
};
