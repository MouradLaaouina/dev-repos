import Router from "express"
import { getWithToken, postWithToken, putWithToken, deleteWithToken } from "../service/axiosService.js"
import { responseToDTO } from "../service/responseService.js"

const agendaRouter = Router()

agendaRouter.get("/", async (req,res)=>{
    const token = req.get('DOLAPIKEY')
    const pageNbr = req.query.page || 0
    try{
        let url =`/agendaevents?sortfield=t.rowid&sortorder=ASC&limit=100&page=${pageNbr}`
        const response = await getWithToken(url,token)
        res.send({
            status:"ok",
            result: responseToDTO(response,null)
        })
    }catch(error){
        res.status(500).send({
            status:"error",
            message: error.message
        })
    }
})

agendaRouter.post("/", async (req,res)=>{
    const token = req.get('DOLAPIKEY')
    try{
        const response = await postWithToken("/agendaevents", req.body, token)
        res.send({
            status:"ok",
            result: response
        })
    }catch(error){
        res.status(500).send({
            status:"error",
            message: error.message
        })
    }
})

agendaRouter.put("/:id", async (req,res)=>{
    const token = req.get('DOLAPIKEY')
    try{
        const response = await putWithToken(`/agendaevents/${req.params.id}`, req.body, token)
        res.send({
            status:"ok",
            result: response
        })
    }catch(error){
        res.status(500).send({
            status:"error",
            message: error.message
        })
    }
})

agendaRouter.delete("/:id", async (req,res)=>{
    const token = req.get('DOLAPIKEY')
    try{
        const response = await deleteWithToken(`/agendaevents/${req.params.id}`, token)
        res.send({
            status:"ok",
            result: response
        })
    }catch(error){
        res.status(500).send({
            status:"error",
            message: error.message
        })
    }
})

export default agendaRouter
