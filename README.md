MyBlog
==========

## BuildTools
*	Static Blog :Jeklly3.3.1
*	Themes change from [dbyll](https://github.com/dbtek/dbyll)

## Reqired Plugin
*	jeklly-paginate

## Local Development Environment
*	Install ruby
*	Install Bundle: ```gem install Bundle```
*	Install jeklly ```gem install jeklly```
*	Create new blog project: ```jeklly new blog```
*	Change Direct to blog: ```cd blog``` ,and start server ```jeklly serve```

## Add Plugin
*	edit _config.yml file ,add plugin in gem file, just like: ``` gem [jeklly-paginate]```
*	edit Gemfile, add plugin name on ```group :jekyll_plugins do```,after that ,file looks like

```
group :jekyll_plugins do
   gem "jekyll-feed", "~> 0.6"
   gem "jekyll-paginate",">=0.0.4"
end
```

*	add _plugins floder blog root director

*	run command to build jeklly project:```bundle build```
*	start jeklly serve:```jeklly serve```

### Tips
*	If do not change anything after create project by jeklly,it will use default themes.If you want to change
themes,you can follow site [https://jekyllrb.com/docs/themes](https://jekyllrb.com/docs/themes/)

* If you want you others themes,you can get someone you like in [https://github.com/jekyll/jekyll/wiki/Themes](https://github.com/jekyll/jekyll/wiki/Themes)






